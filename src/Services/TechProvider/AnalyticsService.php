<?php

namespace ESolution\WhatsApp\Services\TechProvider;

use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class AnalyticsService
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
     * Get WABA analytics (e.g., messaging analytics).
     */
    public function getWabaAnalytics(WhatsappAccount $acc, string $granularity = 'DAY', ?string $start = null, ?string $end = null): array
    {
        $url = "https://graph.facebook.com/v23.0/{$acc->waba_id}/messaging_product_metrics";
        // Note: The endpoint might vary slightly based on exact metric needed (e.g., conversation_analytics).

        $res = $this->client($acc)->get($url, array_filter([
            'granularity' => $granularity,
            'start' => $start,
            'end' => $end,
        ]));

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to get WABA analytics: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Get phone number health status and quality rating.
     */
    public function getPhoneNumberHealth(WhatsappAccount $acc, string $phoneNumberId): array
    {
        $url = "https://graph.facebook.com/v23.0/{$phoneNumberId}";
        $res = $this->client($acc)->get($url, [
            'fields' => 'quality_rating,status,code_verification_status'
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to get phone health: ' . $res->body());
        }

        return $res->json();
    }
}
