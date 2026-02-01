<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Services\TechProvider\MediaService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MediaController extends Controller
{
    public function __construct(protected MediaService $service) {}

    /**
     * Upload media to Meta.
     */
    public function store(Request $request, WhatsappAccount $account)
    {
        $request->validate([
            'file' => 'required|file',
            'type' => 'required|string', // image, video, audio, document, sticker
        ]);

        $path = $request->file('file')->getRealPath();

        return response()->json($this->service->upload($account, $path, $request->type));
    }

    /**
     * Get media URL / metadata.
     */
    public function show(WhatsappAccount $account, string $mediaId)
    {
        return response()->json($this->service->getMedia($account, $mediaId));
    }

    /**
     * Delete media.
     */
    public function destroy(WhatsappAccount $account, string $mediaId)
    {
        $success = $this->service->deleteMedia($account, $mediaId);

        return response()->json(['success' => $success]);
    }
}
