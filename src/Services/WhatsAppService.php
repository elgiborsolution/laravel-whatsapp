<?php

namespace ESolution\WhatsApp\Services;

use ESolution\WhatsApp\Models\{WhatsappAccount, WhatsappMessage, WhatsappToken};
use ESolution\WhatsApp\Traits\NormalizesPhoneNumbers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    use NormalizesPhoneNumbers;

    public function __construct(protected array $config) {}

    protected function endpoint(WhatsappAccount $acc, string $path): string
    {
        $base = rtrim($this->config['base_url'] ?? 'https://graph.facebook.com/v23.0', '/');
        return "{$base}/{$acc->phone_number_id}/{$path}";
    }

    protected function client(WhatsappAccount $acc)
    {
        return Http::withToken($acc->access_token)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    public function sendRaw(WhatsappAccount $acc, array $body): array
    {
        $res = $this->client($acc)->post($this->endpoint($acc, 'messages'), $body);
        if (!$res->successful()) {
            throw new \RuntimeException('WA send failed: ' . $res->body());
        }
        return $res->json();
    }

    public function sendText(WhatsappAccount $acc, string $to, string $text, ?bool $previewUrl = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'text',
            'text' => ['body' => $text, 'preview_url' => (bool)$previewUrl],
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function sendTemplate(WhatsappAccount $acc, string $to, string $templateName, string $lang = 'id_ID', array $components = []): array
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

    public function sendMedia(WhatsappAccount $acc, string $to, string $mediaType, array $mediaPayload): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function sendLocation(WhatsappAccount $acc, string $to, float $lat, float $lng, ?string $name = null, ?string $address = null): array
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

    public function sendInteractive(WhatsappAccount $acc, string $to, array $interactive): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function markAsRead(WhatsappAccount $acc, string $messageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        $res = $this->client($acc)->post($this->endpoint($acc, 'messages'), $payload);
        if (!$res->successful()) {
            throw new \RuntimeException('WA markAsRead failed: ' . $res->body());
        }
        return $res->json();
    }

    public function sendReaction(WhatsappAccount $acc, string $to, string $messageId, string $emoji): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji,
            ],
        ];
        return $this->sendRaw($acc, $payload);
    }

    public function listTemplates(WhatsappAccount $acc, int $limit = 100, ?string $after = null): array
    {
        $url = rtrim($this->config['base_url'], '/') . "/{$acc->waba_id}/message_templates";
        $q = array_filter(['limit' => $limit, 'after' => $after]);
        $res = $this->client($acc)->get($url, $q);
        if (!$res->successful()) throw new \RuntimeException($res->body());
        return $res->json();
    }

    public function createTemplate(WhatsappAccount $acc, array $data): array
    {
        $url = rtrim($this->config['base_url'], '/') . "/{$acc->waba_id}/message_templates";
        $res = $this->client($acc)->post($url, $data);
        if (!$res->successful()) throw new \RuntimeException($res->body());
        return $res->json();
    }

    public function getTemplate(WhatsappAccount $acc, string $templateId): array
    {
        $url = rtrim($this->config['base_url'], '/') . "/{$templateId}";
        $res = $this->client($acc)->get($url);
        if (!$res->successful()) throw new \RuntimeException($res->body());
        return $res->json();
    }

    public function deleteTemplate(WhatsappAccount $acc, string $name, string $language): bool
    {
        $url = rtrim($this->config['base_url'], '/') . "/{$acc->waba_id}/message_templates";
        $res = $this->client($acc)->delete($url, ['name' => $name, 'language' => $language]);
        return $res->successful();
    }

    /**
     * Create a new inbound token (OTP/Voucher/etc).
     *
     * @param string $phone
     * @param string $type
     * @param array $metadata
     * @param array $options [expires_in (min), length, format (alphanumeric|numeric|uuid)]
     * @return WhatsappToken
     */
    public function createToken(string $phone, string $type = 'otp', array $metadata = [], array $options = []): WhatsappToken
    {
        $phone = $this->normalizePhone($phone);
        $format = $options['format'] ?? 'alphanumeric';
        $length = $options['length'] ?? ($format === 'numeric' ? 6 : 8);
        $expiresIn = $options['expires_in'] ?? 10;

        $token = match ($format) {
            'uuid' => (string) Str::uuid(),
            'numeric' => $this->generateNumericToken($length),
            default => Str::upper(Str::random($length)),
        };

        return WhatsappToken::create([
            'phone' => $phone,
            'token' => $token,
            'type' => $type,
            'metadata' => $metadata,
            'expires_at' => $expiresIn ? now()->addMinutes($expiresIn) : null,
        ]);
    }

    /**
     * Attempt to find and consume a token within a message body.
     *
     * @param string $phone
     * @param string $text
     * @return WhatsappToken|null
     */
    public function consumeToken(string $phone, string $text): ?WhatsappToken
    {
        $phone = $this->normalizePhone($phone);
        $tokens = WhatsappToken::active()->where('phone', $phone)->get();

        foreach ($tokens as $token) {
            // Case-insensitive check for the token within the mixed text
            if (Str::contains(Str::lower($text), Str::lower($token->token))) {
                $token->markAsVerified();

                event('whatsapp.token.verified', [$token]);
                event("whatsapp.token.verified.{$token->type}", [$token]);

                return $token;
            }
        }

        return null;
    }

    protected function generateNumericToken(int $length): string
    {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string) rand($min, $max);
    }
}
