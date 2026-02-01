<?php

namespace ESolution\WhatsApp\Tests\Unit;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Facades\WhatsApp;
use Illuminate\Support\Str;

class TokenGenerationTest extends TestCase
{
    public function test_it_generates_alphanumeric_tokens_by_default()
    {
        $token = WhatsApp::createToken('08123456789', 'otp');

        $this->assertEquals(8, strlen($token->token));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $token->token);
    }

    public function test_it_generates_numeric_tokens()
    {
        $token = WhatsApp::createToken('08123456789', 'otp', [], [
            'format' => 'numeric',
            'length' => 6
        ]);

        $this->assertEquals(6, strlen($token->token));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $token->token);
    }

    public function test_it_generates_uuid_tokens()
    {
        $token = WhatsApp::createToken('08123456789', 'otp', [], [
            'format' => 'uuid'
        ]);

        $this->assertTrue(Str::isUuid($token->token));
    }

    public function test_it_saves_metadata()
    {
        $metadata = ['email' => 'test@example.com', 'id' => 123];
        $token = WhatsApp::createToken('08123456789', 'otp', $metadata);

        $this->assertEquals($metadata, $token->metadata);
    }
}
