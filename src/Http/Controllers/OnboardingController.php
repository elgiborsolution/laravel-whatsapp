<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Services\TechProvider\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OnboardingController extends Controller
{
    public function __construct(protected OnboardingService $service) {}

    /**
     * Exchange the short-lived user access token for a long-lived one.
     */
    public function exchangeToken(Request $request)
    {
        $request->validate([
            'short_lived_token' => 'required|string',
        ]);

        return response()->json($this->service->getLongLivedToken($request->short_lived_token));
    }

    /**
     * Get WABA ID and other info using the access token.
     */
    public function debugToken(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        return response()->json($this->service->getSharedWaba($request->access_token));
    }
}
