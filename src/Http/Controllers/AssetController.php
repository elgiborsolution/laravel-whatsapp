<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Services\TechProvider\AssetService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AssetController extends Controller
{
    public function __construct(protected AssetService $service) {}

    /**
     * List business phone numbers for a WABA.
     */
    public function index(WhatsappAccount $account)
    {
        return response()->json($this->service->listPhoneNumbers($account));
    }

    /**
     * Get details of a specific phone number.
     */
    public function show(WhatsappAccount $account, string $phoneNumberId)
    {
        return response()->json($this->service->getPhoneNumber($account, $phoneNumberId));
    }

    /**
     * Register a phone number for Cloud API use.
     */
    public function register(Request $request, WhatsappAccount $account, string $phoneNumberId)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $success = $this->service->registerPhoneNumber($account, $phoneNumberId, $request->pin);

        return response()->json(['success' => $success]);
    }

    /**
     * Verify a phone number by providing the code received via SMS/voice.
     */
    public function verify(Request $request, WhatsappAccount $account, string $phoneNumberId)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $success = $this->service->verifyPhoneNumber($account, $phoneNumberId, $request->code);

        return response()->json(['success' => $success]);
    }
}
