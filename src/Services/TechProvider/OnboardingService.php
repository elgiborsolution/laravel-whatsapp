<?php

namespace ESolution\WhatsApp\Services\TechProvider;

use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class OnboardingService
{
    public function __construct(protected array $config) {}

    protected function client(string $accessToken)
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * Exchange the short-lived user access token from Embedded Signup for a long-lived one.
     * Note: This usually requires your App Secret.
     */
    public function getLongLivedToken(string $shortLivedToken): array
    {
        $res = Http::get("https://graph.facebook.com/v23.0/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->config['client_id'] ?? null,
            'client_secret' => $this->config['client_secret'] ?? null,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to exchange token: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Get WABA ID and other info using the access token returned from Embedded Signup.
     */
    public function getSharedWaba(string $accessToken): array
    {
        $res = $this->client($accessToken)->get("https://graph.facebook.com/v23.0/debug_token", [
            'input_token' => $accessToken,
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to debug token: ' . $res->body());
        }

        $data = $res->json();
        $granterId = data_get($data, 'data.granular_scopes.0.target_ids.0'); // Usually the WABA ID

        return [
            'waba_id' => $granterId,
            'scopes' => data_get($data, 'data.scopes'),
            'raw' => $data,
        ];
    }
}
