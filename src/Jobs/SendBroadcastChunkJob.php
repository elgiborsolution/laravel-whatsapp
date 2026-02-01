<?php

namespace ESolution\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ESolution\WhatsApp\Models\{WhatsappBroadcast, WhatsappBroadcastRecipient, WhatsappMessage, WhatsappAccount};

class SendBroadcastChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = 30;

    public function __construct(public int $broadcastId, public array $recipientIds) {}

    public function handle(): void
    {
        $b = WhatsappBroadcast::find($this->broadcastId);
        if (!$b || $b->status === 'paused') return;

        $acc = WhatsappAccount::resolve($b->whatsapp_account_id);
        $rpm = max(60, (int)$b->rate_per_min);

        foreach (WhatsappBroadcastRecipient::whereIn('id', $this->recipientIds)->cursor() as $rec) {
            RateLimiter::attempt(
                'wa-broadcast:' . $acc->phone_number_id,
                $rpm,
                function () use ($b, $acc, $rec) {
                    $msg = WhatsappMessage::create([
                        'whatsapp_account_id' => $acc->id ?: null,
                        'to'   => $rec->to,
                        'type' => $b->type,
                        'payload' => array_merge($b->payload, ['broadcast_id' => $b->id]),
                        'status' => 'queued',
                    ]);
                    dispatch(new \ESolution\WhatsApp\Jobs\SendMessageJob($msg->id))
                        ->onConnection(config('whatsapp.queue'));
                    $rec->status = 'queued';
                    $rec->save();
                },
                60
            );
        }
    }
}
