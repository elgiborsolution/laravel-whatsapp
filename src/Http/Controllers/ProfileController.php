<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Services\TechProvider\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $service) {}

    /**
     * Get WhatsApp Business Profile.
     */
    public function show(WhatsappAccount $account, string $phoneNumberId)
    {
        return response()->json($this->service->getProfile($account, $phoneNumberId));
    }

    /**
     * Update WhatsApp Business Profile.
     */
    public function update(Request $request, WhatsappAccount $account, string $phoneNumberId)
    {
        $request->validate([
            'about' => 'nullable|string',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'email' => 'nullable|email',
            'profile_picture_url' => 'nullable|url',
            'websites' => 'nullable|array',
            'vertical' => 'nullable|string',
        ]);

        $success = $this->service->updateProfile($account, $phoneNumberId, $request->all());

        return response()->json(['success' => $success]);
    }
}
