<?php

namespace ESolution\WhatsApp\Tests\Unit\TechProvider;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Services\TechProvider\OnboardingService;
use ESolution\WhatsApp\Services\TechProvider\AssetService;
use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class OnboardingTest extends TestCase
{
    public function test_it_can_exchange_long_lived_token()
    {
        Http::fake([
            'graph.facebook.com/v23.0/oauth/access_token*' => Http::response([
                'access_token' => 'long-lived-token',
                'token_type' => 'bearer',
                'expires_in' => 5184000
            ], 200)
        ]);

        $service = new OnboardingService([
            'client_id' => '123',
            'client_secret' => 'secret'
        ]);

        $res = $service->getLongLivedToken('short-token');

        $this->assertEquals('long-lived-token', $res['access_token']);
    }

    public function test_it_can_get_shared_waba()
    {
        Http::fake([
            'graph.facebook.com/v23.0/debug_token*' => Http::response([
                'data' => [
                    'granular_scopes' => [
                        [
                            'target_ids' => ['WABA_ID']
                        ]
                    ],
                    'scopes' => ['whatsapp_business_management']
                ]
            ], 200)
        ]);

        $service = new OnboardingService([]);
        $res = $service->getSharedWaba('some-token');

        $this->assertEquals('WABA_ID', $res['waba_id']);
    }

    public function test_it_can_list_phone_numbers()
    {
        Http::fake([
            'graph.facebook.com/v23.0/WABA_ID/phone_numbers*' => Http::response([
                'data' => [
                    ['id' => 'PHONE_ID_1', 'display_phone_number' => '+62123']
                ]
            ], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token', 'waba_id' => 'WABA_ID']);
        $service = new AssetService([]);
        $res = $service->listPhoneNumbers($acc);

        $this->assertCount(1, $res['data']);
        $this->assertEquals('PHONE_ID_1', $res['data'][0]['id']);
    }
}
