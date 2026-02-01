<?php

namespace ESolution\WhatsApp\Services\TechProvider;

use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class MediaService
{
    public function __construct(protected array $config) {}

    protected function client(WhatsappAccount $acc)
    {
        return Http::withToken($acc->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(60); // Media can be large
    }

    /**
     * Upload media to Meta.
     */
    public function upload(WhatsappAccount $acc, string $filePath, string $type): array
    {
        $url = "https://graph.facebook.com/v23.0/{$acc->phone_number_id}/media";

        $res = Http::withToken($acc->access_token)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'type' => $type,
            ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to upload media: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Get media URL / metadata.
     */
    public function getMedia(WhatsappAccount $acc, string $mediaId): array
    {
        $url = "https://graph.facebook.com/v23.0/{$mediaId}";
        $res = $this->client($acc)->get($url);

        if (!$res->successful()) {
            throw new \RuntimeException('Failed to get media: ' . $res->body());
        }

        return $res->json();
    }

    /**
     * Delete media.
     */
    public function deleteMedia(WhatsappAccount $acc, string $mediaId): bool
    {
        $url = "https://graph.facebook.com/v23.0/{$mediaId}";
        $res = $this->client($acc)->delete($url);

        return $res->successful();
    }
}
