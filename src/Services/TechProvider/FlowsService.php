<?php

namespace ESolution\WhatsApp\Services\TechProvider;

use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class FlowsService
{
    public function __construct(protected array $config) {}

    protected function baseUrl(string $path = ''): string
    {
        $base = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com/v23.0', '/');
        return $path ? "{$base}/" . ltrim($path, '/') : $base;
    }

    protected function client(WhatsappAccount $acc)
    {
        return Http::withToken($acc->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * Create a new WhatsApp Flow.
     */
    public function createFlow(WhatsappAccount $acc, string $name, array $categories): array
    {
        $url = $this->baseUrl("{$acc->waba_id}/flows");
        $res = $this->client($acc)->post($url, [
            'name' => $name,
            'categories' => $categories,
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to create flow: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Update a flow (e.g., upload JSON asset).
     */
    public function updateFlowAsset(WhatsappAccount $acc, string $flowId, string $jsonPath): bool
    {
        $url = $this->baseUrl("{$flowId}/assets");

        $res = Http::withToken($acc->access_token)
            ->attach('file', file_get_contents($jsonPath), 'flow.json')
            ->post($url, [
                'name' => 'flow-asset',
                'asset_type' => 'FLOW_JSON',
            ]);

        return $res->successful();
    }

    /**
     * Publish a flow.
     */
    public function publishFlow(WhatsappAccount $acc, string $flowId): bool
    {
        $url = $this->baseUrl("{$flowId}/publish");
        $res = $this->client($acc)->post($url);

        return $res->successful();
    }

    /**
     * List all flows for a WABA.
     */
    public function listFlows(WhatsappAccount $acc): array
    {
        $url = $this->baseUrl("{$acc->waba_id}/flows");
        $res = $this->client($acc)->get($url);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to list flows: ' . $res->body());
        }

        return $res->json();
    }
}
