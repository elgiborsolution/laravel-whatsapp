<?php

namespace ESolution\WhatsApp\Services\TechProvider;

use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class AssetService
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
     * List business phone numbers for a WABA.
     */
    public function listPhoneNumbers(WhatsappAccount $acc): array
    {
        $url = "https://graph.facebook.com/v23.0/{$acc->waba_id}/phone_numbers";
        $res = $this->client($acc)->get($url);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to list phone numbers: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Get details of a specific phone number.
     */
    public function getPhoneNumber(WhatsappAccount $acc, string $phoneNumberId): array
    {
        $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}";
        $res = $this->client($acc)->get($url);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to get phone number: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Register a phone number for Cloud API use.
     */
    public function registerPhoneNumber(WhatsappAccount $acc, string $phoneNumberId, string $pin): bool
    {
        $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/register";
        $res = $this->client($acc)->post($url, [
            'messaging_product' => 'whatsapp',
            'pin' => $pin,
        ]);

        return $res->successful();
    }

    /**
     * Verify a phone number by providing the code received via SMS/voice.
     */
    public function verifyPhoneNumber(WhatsappAccount $acc, string $phoneNumberId, string $code): bool
    {
        $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}/verify";
        $res = $this->client($acc)->post($url, [
            'code' => $code,
        ]);

        return $res->successful();
    }
}
