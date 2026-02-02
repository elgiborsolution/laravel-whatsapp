<?php

namespace ESolution\WhatsApp\Tests\Unit\Jobs;

use ESolution\WhatsApp\Jobs\ForwardWebhookJob;
use ESolution\WhatsApp\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForwardWebhookJobTest extends TestCase
{
    public function test_job_sends_post_request_to_url()
    {
        Http::fake();

        $url = 'https://example.com/webhook';
        $payload = ['foo' => 'bar'];

        $job = new ForwardWebhookJob($url, $payload);
        $job->handle();

        Http::assertSent(function ($request) use ($url, $payload) {
            return $request->url() === $url &&
                $request->method() === 'POST' &&
                $request->data() === $payload;
        });
    }

    public function test_job_throws_exception_on_failure()
    {
        Http::fake([
            '*' => Http::response('Error', 500)
        ]);

        $url = 'https://example.com/webhook';
        $payload = ['foo' => 'bar'];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Webhook forwarding failed with status 500');

        $job = new ForwardWebhookJob($url, $payload);
        $job->handle();
    }
}
