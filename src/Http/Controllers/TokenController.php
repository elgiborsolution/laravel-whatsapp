<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TokenController extends Controller
{
    public function __construct(protected WhatsAppService $service) {}

    /**
     * Create a new inbound token.
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'type' => 'nullable|string',
            'metadata' => 'nullable|array',
            'options' => 'nullable|array',
        ]);

        $token = $this->service->createToken(
            $request->phone,
            $request->input('type', 'otp'),
            $request->input('metadata', []),
            $request->input('options', [])
        );

        return response()->json($token);
    }

    /**
     * Manually consume/verify a token from text.
     */
    public function consume(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'text' => 'required|string',
        ]);

        $token = $this->service->consumeToken($request->phone, $request->text);

        if (!$token) {
            return response()->json(['message' => 'Token not found or expired'], 404);
        }

        return response()->json($token);
    }
}
