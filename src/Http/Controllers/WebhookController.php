<?php

namespace ESolution\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ESolution\WhatsApp\Models\{WhatsAppMessage, WhatsAppBroadcastRecipient};

class WebhookController extends Controller
{
    public function verify(Request $r)
    {
        if ($r->get('hub_mode') === 'subscribe' && $r->get('hub_verify_token') === config('whatsapp.webhook_verify_token')) {
            return response($r->get('hub_challenge'), 200);
        }
        return response('Invalid verify token', 403);
    }

    public function handle(Request $r)
    {
        $data = $r->all();
        foreach (($data['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $statuses = $value['statuses'] ?? [];
                foreach ($statuses as $st) {
                    $waId = $st['id'] ?? null;
                    $status = $st['status'] ?? null;
                    $ts = isset($st['timestamp']) ? now()->createFromTimestamp($st['timestamp']) : now();

                    if ($waId && $status) {
                        $m = WhatsAppMessage::where('wa_message_id', $waId)->first();
                        if ($m) {
                            $m->status = $status;
                            if ($status === 'sent') $m->sent_at = $ts;
                            if ($status === 'delivered') $m->delivered_at = $ts;
                            if ($status === 'read') $m->read_at = $ts;
                            if ($status === 'failed') {
                                $err = $st['errors'][0] ?? [];
                                $m->error_code = $err['code'] ?? null;
                                $m->error_title = $err['title'] ?? null;
                                $m->error_details = $err['details'] ?? null;
                            }
                            $m->save();

                            WhatsAppBroadcastRecipient::where('wa_message_id', $waId)->update([
                                'status' => $status,
                                'sent_at' => $m->sent_at,
                                'delivered_at' => $m->delivered_at,
                                'read_at' => $m->read_at,
                                'error_code' => $m->error_code,
                                'error_title' => $m->error_title,
                                'error_details' => $m->error_details,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
        return response()->json(['ok'=>true]);
    }
}
