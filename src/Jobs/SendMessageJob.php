<?php

namespace ESolution\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ESolution\WhatsApp\Models\{WhatsAppMessage, WhatsAppAccount, WhatsAppBroadcastRecipient};
use ESolution\WhatsApp\Services\WhatsAppService;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;

    public function __construct(public int $messageId) {}

    public function handle(WhatsAppService $svc): void
    {
        $msg = WhatsAppMessage::find($this->messageId);
        if (!$msg) return;

        $acc = WhatsAppAccount::resolve($msg->whatsapp_account_id);
        $payload = $msg->payload;
        $to = $msg->to;

        $resp = match ($msg->type) {
            'text' => $svc->sendText($acc, $to, $payload['body'] ?? '', $payload['preview_url'] ?? null),
            'template' => $svc->sendTemplate($acc, $to, $payload['name'], $payload['language'] ?? 'en_US', $payload['components'] ?? []),
            'image','audio','video','document' => $svc->sendMedia($acc, $to, $msg->type, $payload),
            'location' => $svc->sendLocation($acc, $to, (float)$payload['lat'], (float)$payload['lng'], $payload['name'] ?? null, $payload['address'] ?? null),
            'interactive' => $svc->sendInteractive($acc, $to, $payload),
            default => throw new \InvalidArgumentException('Unsupported type '.$msg->type),
        };

        $waId = $resp['messages'][0]['id'] ?? null;
        $msg->wa_message_id = $waId;
        $msg->status = 'sent';
        $msg->sent_at = now();
        $msg->save();

        if ($waId) {
            WhatsAppBroadcastRecipient::where('whatsapp_broadcast_id', $payload['broadcast_id'] ?? 0)
                ->where('to', $msg->to)
                ->update(['wa_message_id'=>$waId,'status'=>'sent','sent_at'=>now()]);
        }
    }
}
