<?php

namespace ESolution\WhatsApp\Services\TechProvider;

use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class ProfileService
{
    public function __construct(protected array $config) {}

    protected function client(WhatsappAccount $acc)
    {
        return Http::withToken($acc->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * Get WhatsApp Business Profile.
     */
    public function getProfile(WhatsappAccount $acc, string $phoneNumberId): array
    {
        $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/whatsapp_business_profile";
        $res = $this->client($acc)->get($url);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to get profile: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Update WhatsApp Business Profile.
     */
    public function updateProfile(WhatsappAccount $acc, string $phoneNumberId, array $data): bool
    {
        $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/whatsapp_business_profile";
        $res = $this->client($acc)->post($url, array_merge($data, [
            'messaging_product' => 'whatsapp',
        ]));

        return $res->successful();
    }
}
