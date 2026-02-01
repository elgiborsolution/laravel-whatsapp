<?php

namespace ESolution\WhatsApp\Tests\Unit\TechProvider;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Services\TechProvider\ProfileService;
use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class ProfileTest extends TestCase
{
    public function test_it_can_get_profile()
    {
        Http::fake([
            'graph.facebook.com/v23.0/PHONE_ID/whatsapp_business_profile*' => Http::response([
                'data' => [
                    [
                        'about' => 'Hello',
                        'email' => 'test@example.com'
                    ]
                ]
            ], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token']);
        $service = new ProfileService([]);
        $res = $service->getProfile($acc, 'PHONE_ID');

        $this->assertEquals('Hello', $res['data'][0]['about']);
    }

    public function test_it_can_update_profile()
    {
        Http::fake([
            'graph.facebook.com/v23.0/PHONE_ID/whatsapp_business_profile' => Http::response(['success' => true], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token']);
        $service = new ProfileService([]);
        $res = $service->updateProfile($acc, 'PHONE_ID', ['about' => 'New About']);

        $this->assertTrue($res);
    }
}
