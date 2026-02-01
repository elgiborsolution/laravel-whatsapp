<?php

namespace ESolution\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ESolution\WhatsApp\Models\{
    WhatsAppMessage,
    WhatsappBroadcastRecipient
};
use ESolution\WhatsApp\Traits\NormalizesPhoneNumbers;
use ESolution\WhatsApp\Services\WhatsAppService;

class WebhookController extends Controller
{
    use NormalizesPhoneNumbers;

    public function __construct(protected WhatsAppService $whatsapp) {}
    /**
     * GET verification endpoint used by Meta (hub.challenge)
     */
    public function verify(Request $r)
    {
        if ($r->get('hub_mode') === 'subscribe' && $r->get('hub_verify_token') === config('whatsapp.webhook_verify_token')) {
            return response($r->get('hub_challenge'), 200);
        }
        return response('Invalid verify token', 403);
    }

    /**
     * POST webhook handler
     * - delivery statuses (sent/delivered/read/failed)
     * - inbound messages (including call_permission_reply)
     */
    public function handle(Request $r)
    {
        $payload = $r->all();

        foreach ((array)($payload['entry'] ?? []) as $entry) {
            foreach ((array)($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];

                // 1) Delivery status updates
                if (!empty($value['statuses'])) {
                    $this->processStatuses($value['statuses']);
                }

                // 2) Inbound messages (including call permission replies)
                if (!empty($value['messages'])) {
                    $this->processInboundMessages($value['messages'], $value);
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Update message & broadcast recipient statuses from Meta "statuses" array.
     */
    protected function processStatuses(array $statuses): void
    {
        foreach ($statuses as $st) {
            $waId   = $st['id']     ?? null;  // wamid
            $status = $st['status'] ?? null;  // sent | delivered | read | failed
            $ts     = isset($st['timestamp']) ? Carbon::createFromTimestamp((int)$st['timestamp']) : now();

            if (!$waId || !$status) continue;

            $m = WhatsAppMessage::where('wa_message_id', $waId)->first();
            if ($m) {
                $m->status = $status;
                if ($status === 'sent')      $m->sent_at      = $ts;
                if ($status === 'delivered') $m->delivered_at = $ts;
                if ($status === 'read')      $m->read_at      = $ts;

                if ($status === 'failed') {
                    $err = $st['errors'][0] ?? [];
                    $m->error_code    = $err['code']   ?? null;
                    $m->error_title   = $err['title']  ?? null;
                    $m->error_details = $err['details'] ?? null;
                }
                $m->save();

                // If part of a broadcast
                WhatsappBroadcastRecipient::where('wa_message_id', $waId)->update([
                    'status'        => $status,
                    'sent_at'       => $m->sent_at,
                    'delivered_at'  => $m->delivered_at,
                    'read_at'       => $m->read_at,
                    'error_code'    => $m->error_code,
                    'error_title'   => $m->error_title,
                    'error_details' => $m->error_details,
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    /**
     * Handle inbound messages to detect call-permission responses (including the new
     * interactive.type = "call_permission_reply" shape you shared).
     */
    protected function processInboundMessages(array $messages, array $rootValue): void
    {
        $metadataPhoneId = data_get($rootValue, 'metadata.phone_number_id');

        foreach ($messages as $msg) {
            // Persist inbound as WhatsAppMessage (optional but useful for audits)
            $from = $this->normalizePhone($msg['from'] ?? '');
            $type = $msg['type'] ?? 'unknown';

            $stored = new WhatsAppMessage();
            $stored->whatsapp_account_id = null; // you can map by $metadataPhoneId to your accounts table if needed
            $stored->to            = $from; // inbound "from" becomes our "to" when replying
            $stored->type          = 'inbound:' . $type;
            $stored->payload       = $msg;
            $stored->wa_message_id = $msg['id'] ?? null;
            $stored->status        = 'received';
            $stored->save();

            // Detect and consume tokens (OTP, Voucher, etc.)
            if ($type === 'text') {
                $text = data_get($msg, 'text.body');
                if ($text) {
                    $this->whatsapp->consumeToken($from, $text);
                }
            }

            // Detect call permission reply (supports call_permission_reply + fallback keywords/buttons)
            $decision = $this->detectCallPermissionDecision($msg);

            if ($decision['status'] !== 'unknown') {
                // You may capture related context like template or original message id
                $contextMessageId = data_get($msg, 'context.id'); // wacid of the referenced outbound msg
                $templateName = data_get($msg, 'context.metadata.template.name'); // only if you store this on send

                // Dispatch a simple string event for your app to handle DB updates
                $payload = [
                    'from' => $from,
                    'status' => $decision['status'],
                    'expires_at' => $decision['expires_at'],
                    'response_source' => $decision['response_source'],
                    'raw' => $msg,
                    'phone_number_id' => $metadataPhoneId,
                    'context_message_id' => $contextMessageId,
                    'template_name' => $templateName,
                ];

                event('whatsapp.call_permission.updated', [$payload]);

                Log::info('[WA] Call permission decision', [
                    'from'            => $from,
                    'status'          => $decision['status'],
                    'expires_at'      => optional($decision['expires_at'])->toIso8601String(),
                    'response_source' => $decision['response_source'],
                    'phone_number_id' => $metadataPhoneId,
                ]);
            }
        }
    }

    /**
     * Determine call permission decision from the inbound message.
     * Supports:
     * - interactive.type = "call_permission_reply" with { response, expiration_timestamp, response_source }
     *
     * Returns array: ['status' => approved|declined|unknown, 'expires_at' => Carbon|null, 'response_source' => ?string]
     */
    protected function detectCallPermissionDecision(array $msg): array
    {
        $result = [
            'status' => 'unknown',
            'expires_at' => null,
            'response_source' => null,
        ];

        $type = $msg['type'] ?? '';

        // A) New official structure: interactive.type = "call_permission_reply"
        if ($type === 'interactive' && data_get($msg, 'interactive.type') === 'call_permission_reply') {
            $response = Str::of((string) data_get($msg, 'interactive.call_permission_reply.response', ''))->lower()->value();
            $expTs    = data_get($msg, 'interactive.call_permission_reply.expiration_timestamp');
            $source   = data_get($msg, 'interactive.call_permission_reply.response_source'); // user_action | automatic
            $result['status'] = 'declined';

            if ($response === 'accept' || $response === 'approved' || $response === 'allow') {
                $result['status'] = 'approved';
            }

            if (!empty($expTs)) {
                // expiration_timestamp may be a unix epoch seconds string
                $ts = is_numeric($expTs) ? (int) $expTs : null;
                if ($ts) $result['expires_at'] = Carbon::createFromTimestamp($ts);
            }

            $result['response_source'] = $source ?: null;
            return $result;
        }

        return $result;
    }
}
