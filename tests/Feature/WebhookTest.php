<?php

namespace ESolution\WhatsApp\Tests\Feature;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Facades\WhatsApp;
use ESolution\WhatsApp\Models\WhatsappToken;
use Illuminate\Support\Facades\Event;

class WebhookTest extends TestCase
{
    public function test_webhook_consumes_token_from_inbound_text()
    {
        Event::fake();

        $phone = '628123456789';
        $token = WhatsApp::createToken($phone, 'otp', [], ['length' => 8]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['display_phone_number' => '12345', 'phone_number_id' => '67890'],
                                'contacts' => [['profile' => ['name' => 'John'], 'wa_id' => $phone]],
                                'messages' => [
                                    [
                                        'from' => $phone,
                                        'id' => 'wamid.HBgLNjI4MTIzNDU2Nzg5FQIAERgSQjU1N0REODU0MkU3OUIyODlFAA==',
                                        'timestamp' => '1603010000',
                                        'text' => ['body' => "Verify me: {$token->token}"],
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
        $this->assertNotNull($token->fresh()->verified_at);
        Event::assertDispatched('whatsapp.token.verified');
    }

    public function test_webhook_verification_endpoint()
    {
        config(['whatsapp.webhook_verify_token' => 'secret']);

        $response = $this->get('/whatsapp/webhook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'secret',
            'hub_challenge' => '1234'
        ]));

        $response->assertStatus(200);
        $this->assertEquals('1234', $response->getContent());
    }
}
