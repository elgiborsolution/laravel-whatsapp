<?php

namespace ESolution\WhatsApp\Tests\Feature;

use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Models\WhatsappToken;
use ESolution\WhatsApp\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class TechProviderApiTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsappAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = WhatsappAccount::create([
            'name' => 'test',
            'phone_number_id' => '12345',
            'waba_id' => '67890',
            'access_token' => 'fake-token',
            'is_default' => true,
        ]);
    }

    public function test_can_list_phone_numbers()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['data' => []], 200),
        ]);

        $response = $this->getJson("/whatsapp/accounts/{$this->account->id}/phone-numbers");

        $response->assertStatus(200);
    }

    public function test_can_create_flow()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'flow-123'], 200),
        ]);

        $response = $this->postJson("/whatsapp/accounts/{$this->account->id}/flows", [
            'name' => 'Test Flow',
            'categories' => ['SURVEY'],
        ]);

        $response->assertStatus(200)
            ->assertJson(['id' => 'flow-123']);
    }

    public function test_can_create_token()
    {
        $response = $this->postJson('/whatsapp/tokens', [
            'phone' => '628123456789',
            'type' => 'otp',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('whatsapp_tokens', [
            'phone' => '628123456789',
            'type' => 'otp',
        ]);
    }

    public function test_can_consume_token()
    {
        $token = WhatsappToken::create([
            'phone' => '628123456789',
            'token' => 'ABC123',
            'type' => 'otp',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/whatsapp/tokens/consume', [
            'phone' => '628123456789',
            'text' => 'My code is ABC123',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($token->fresh()->verified_at);
    }
}
