<?php

namespace ESolution\WhatsApp\Tests\Feature;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Jobs\ForwardWebhookJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;

class WebhookForwardingTest extends TestCase
{
    public function test_webhook_dispatches_forwarding_job_if_url_is_set()
    {
        Queue::fake();

        // Setup account with forwarding URL
        $phoneId = '67890';
        WhatsappAccount::create([
            'name' => 'Test Account',
            'phone_number_id' => $phoneId,
            'access_token' => 'dummy',
            'webhook_forward_url' => 'https://example.com/forward'
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['display_phone_number' => '12345', 'phone_number_id' => $phoneId],
                                'messages' => [
                                    [
                                        'from' => '628123456789',
                                        'id' => 'wamid.123',
                                        'text' => ['body' => 'Hello'],
                                        'type' => 'text'
                                    ]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertStatus(200);

        Queue::assertPushed(ForwardWebhookJob::class, function ($job) use ($payload) {
            return $job->url === 'https://example.com/forward' &&
                $job->payload === $payload;
        });
    }

    public function test_webhook_does_not_dispatch_forwarding_job_if_url_is_null()
    {
        Queue::fake();

        $phoneId = '67890';
        WhatsappAccount::create([
            'name' => 'No Forward Account',
            'phone_number_id' => $phoneId,
            'access_token' => 'dummy',
            'webhook_forward_url' => null
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['display_phone_number' => '12345', 'phone_number_id' => $phoneId],
                                'messages' => [
                                    [
                                        'from' => '628123456789',
                                        'id' => 'wamid.123',
                                        'text' => ['body' => 'Hello'],
                                        'type' => 'text'
                                    ]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertStatus(200);

        Queue::assertNotPushed(ForwardWebhookJob::class);
    }
}
