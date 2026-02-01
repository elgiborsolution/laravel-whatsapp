<?php

namespace ESolution\WhatsApp\Tests\Unit\TechProvider;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Services\TechProvider\FlowsService;
use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class FlowsTest extends TestCase
{
    public function test_it_can_create_flow()
    {
        Http::fake([
            'graph.facebook.com/v23.0/WABA_ID/flows' => Http::response(['id' => 'FLOW_ID'], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token', 'waba_id' => 'WABA_ID']);
        $service = new FlowsService([]);
        $res = $service->createFlow($acc, 'My Flow', ['SURVEY']);

        $this->assertEquals('FLOW_ID', $res['id']);
    }

    public function test_it_can_publish_flow()
    {
        Http::fake([
            'graph.facebook.com/v23.0/FLOW_ID/publish' => Http::response(['success' => true], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token']);
        $service = new FlowsService([]);
        $res = $service->publishFlow($acc, 'FLOW_ID');

        $this->assertTrue($res);
    }
}
