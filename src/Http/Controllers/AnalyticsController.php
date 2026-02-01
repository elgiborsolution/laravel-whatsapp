<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Services\TechProvider\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $service) {}

    /**
     * Get WABA messaging metrics.
     */
    public function wabaMetrics(Request $request, WhatsappAccount $account)
    {
        $request->validate([
            'granularity' => 'nullable|string|in:HALF_HOUR,DAY,MONTH',
            'start' => 'nullable|string',
            'end' => 'nullable|string',
        ]);

        return response()->json($this->service->getWabaAnalytics(
            $account,
            $request->query('granularity', 'DAY'),
            $request->query('start'),
            $request->query('end')
        ));
    }

    /**
     * Get phone number health status and quality rating.
     */
    public function phoneHealth(WhatsappAccount $account, string $phoneNumberId)
    {
        return response()->json($this->service->getPhoneNumberHealth($account, $phoneNumberId));
    }
}
