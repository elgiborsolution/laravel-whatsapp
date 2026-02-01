<?php

namespace ESolution\WhatsApp\Tests\Integration;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Facades\WhatsApp;
use ESolution\WhatsApp\Models\WhatsappToken;
use Illuminate\Support\Facades\Event;

class TokenServiceTest extends TestCase
{
    public function test_it_consumes_token_from_mixed_message()
    {
        Event::fake();

        $phone = '628123456789';
        $token = WhatsApp::createToken($phone, 'voucher');
        $code = $token->token;

        $mixedMessage = "Hello, here is my voucher code: {$code}. Thank you!";

        $consumed = WhatsApp::consumeToken($phone, $mixedMessage);

        $this->assertNotNull($consumed);
        $this->assertEquals($token->id, $consumed->id);
        $this->assertNotNull($consumed->verified_at);

        // Assert Events
        Event::assertDispatched('whatsapp.token.verified');
        Event::assertDispatched('whatsapp.token.verified.voucher');
    }

    public function test_it_does_not_consume_expired_token()
    {
        $phone = '628123456789';
        $token = WhatsApp::createToken($phone, 'otp', [], ['expires_in' => -1]); // already expired

        $consumed = WhatsApp::consumeToken($phone, "My code is {$token->token}");

        $this->assertNull($consumed);
    }

    public function test_it_is_case_insensitive()
    {
        $phone = '628123456789';
        $token = WhatsApp::createToken($phone, 'otp', [], ['length' => 8]); // e.g. ABCDE123
        $code = strtolower($token->token); // abcde123

        $consumed = WhatsApp::consumeToken($phone, "code: {$code}");

        $this->assertNotNull($consumed);
        $this->assertNotNull($consumed->verified_at);
    }

    public function test_it_only_matches_token_for_correct_phone()
    {
        $token = WhatsApp::createToken('62811111111', 'otp');

        $consumed = WhatsApp::consumeToken('62822222222', "Code is {$token->token}");

        $this->assertNull($consumed);
    }
}
