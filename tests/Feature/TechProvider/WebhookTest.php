<?php

namespace ESolution\WhatsApp\Tests\Feature\TechProvider;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_verify_webhook()
    {
        $res = $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=test-token&hub_challenge=1234');

        $res->assertStatus(200);
        $res->assertSee('1234');
    }

    public function test_it_can_handle_inbound_text_message()
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['display_phone_number' => '123', 'phone_number_id' => 'PHONE_ID'],
                                'contacts' => [['profile' => ['name' => 'John'], 'wa_id' => '628123']],
                                'messages' => [
                                    [
                                        'from' => '628123',
                                        'id' => 'wamid.HBgNNjI4MTIzNDU2Nzg5FQIAERgSQ0VDODlFOUVCQUI4OUE0OEEzAA==',
                                        'timestamp' => '1623123456',
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

        $res = $this->postJson('/whatsapp/webhook', $payload);

        $res->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '628123',
            'status' => 'received'
        ]);
    }

    public function test_it_can_handle_status_update()
    {
        $m = WhatsappMessage::create([
            'to' => '628123',
            'wa_message_id' => 'wamid.123',
            'status' => 'sent',
            'type' => 'text',
            'payload' => []
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
                                'metadata' => ['display_phone_number' => '123', 'phone_number_id' => 'PHONE_ID'],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.123',
                                        'status' => 'delivered',
                                        'timestamp' => '1623123456',
                                        'recipient_id' => '628123'
                                    ]
                                ]
                            ],
                            'field' => 'messages'
                        ]
                    ]
                ]
            ]
        ];

        $res = $this->postJson('/whatsapp/webhook', $payload);

        $res->assertStatus(200);
        $this->assertEquals('delivered', $m->fresh()->status);
    }
}
