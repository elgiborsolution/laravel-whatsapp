<?php

namespace ESolution\WhatsApp\Tests\Unit\TechProvider;

use ESolution\WhatsApp\Tests\TestCase;
use ESolution\WhatsApp\Services\TechProvider\MediaService;
use ESolution\WhatsApp\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;

class MediaTest extends TestCase
{
    public function test_it_can_upload_media()
    {
        Http::fake([
            'graph.facebook.com/v23.0/PHONE_ID/media' => Http::response(['id' => 'MEDIA_ID'], 200)
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'wa_test');
        file_put_contents($tempFile, 'test content');

        $acc = new WhatsappAccount(['access_token' => 'token', 'phone_number_id' => 'PHONE_ID']);
        $service = new MediaService([]);
        $res = $service->upload($acc, $tempFile, 'image');

        $this->assertEquals('MEDIA_ID', $res['id']);
        unlink($tempFile);
    }

    public function test_it_can_get_media_metadata()
    {
        Http::fake([
            'graph.facebook.com/v23.0/MEDIA_ID' => Http::response([
                'url' => 'https://wa.me/media/url',
                'mime_type' => 'image/jpeg'
            ], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token']);
        $service = new MediaService([]);
        $res = $service->getMedia($acc, 'MEDIA_ID');

        $this->assertEquals('image/jpeg', $res['mime_type']);
    }

    public function test_it_can_delete_media()
    {
        Http::fake([
            'graph.facebook.com/v23.0/MEDIA_ID' => Http::response(['success' => true], 200)
        ]);

        $acc = new WhatsappAccount(['access_token' => 'token']);
        $service = new MediaService([]);
        $res = $service->deleteMedia($acc, 'MEDIA_ID');

        $this->assertTrue($res);
    }
}
