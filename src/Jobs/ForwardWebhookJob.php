<?php

namespace ESolution\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForwardWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $url,
        public array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'User-Agent'   => 'Laravel-WhatsApp-Forwarder/1.0',
        ])
            ->timeout(30)
            ->post($this->url, $this->payload);

        if (!$response->successful()) {
            Log::warning('[WA] Webhook forwarding failed', [
                'url'    => $this->url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException("Webhook forwarding failed with status {$response->status()}");
        }
    }
}
