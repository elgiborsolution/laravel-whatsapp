<?php

namespace ESolution\WhatsApp\Services;

use ESolution\WhatsApp\Models\{WhatsAppAccount, WhatsAppMessage};
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function __construct(protected array $config) {}

    protected function endpoint(WhatsAppAccount $acc, string $path): string
    {
        $base = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com/v23.0','/');
        return "{$base}/{$acc->phone_number_id}/{$path}";
    }

    protected function client(WhatsAppAccount $acc)
    {
        return Http::withToken($acc->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    public function sendRaw(WhatsAppAccount $acc, array $body): array
    {
        $res = $this->client($acc)->post($this->endpoint($acc, 'messages'), $body);
        if (!$res->successful()) {
            throw new \RuntimeException('WA send failed: '.$res->body());
        }
        return $res->json();
    }

    public function sendText(WhatsAppAccount $acc, string $to, string $text, ?bool $previewUrl = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'text',
            'text' => ['body' => $text, 'preview_url' => (bool)$previewUrl],
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function sendTemplate(WhatsAppAccount $acc, string $to, string $templateName, string $lang = 'id_ID', array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $lang],
                'components' => $components,
            ],
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function sendMedia(WhatsAppAccount $acc, string $to, string $mediaType, array $mediaPayload): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function sendLocation(WhatsAppAccount $acc, string $to, float $lat, float $lng, ?string $name=null, ?string $address=null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'location',
            'location' => [
                'latitude' => $lat,
                'longitude' => $lng,
                'name' => $name,
                'address' => $address,
            ],
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function sendInteractive(WhatsAppAccount $acc, string $to, array $interactive): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function listTemplates(WhatsAppAccount $acc, int $limit = 100, ?string $after = null): array
    {
        $url = rtrim($this->config['base_url'],'/')."/{$acc->waba_id}/message_templates";
        $q = array_filter(['limit'=>$limit, 'after'=>$after]);
        $res = $this->client($acc)->get($url, $q);
        if (!$res->successful()) throw new \RuntimeException($res->body());
        return $res->json();
    }

    public function createTemplate(WhatsAppAccount $acc, array $data): array
    {
        $url = rtrim($this->config['base_url'],'/')."/{$acc->waba_id}/message_templates";
        $res = $this->client($acc)->post($url, $data);
        if (!$res->successful()) throw new \RuntimeException($res->body());
        return $res->json();
    }

    public function deleteTemplate(WhatsAppAccount $acc, string $name, string $language): bool
    {
        $url = rtrim($this->config['base_url'],'/')."/{$acc->waba_id}/message_templates";
        $res = $this->client($acc)->delete($url, ['name'=>$name,'language'=>$language]);
        return $res->successful();
    }

    public function normalizePhone(string $to): string
    {
        $n = preg_replace('/\D+/', '', $to);
        if (str_starts_with($n, '0')) $n = '62'.substr($n,1);
        return $n;
    }
}
