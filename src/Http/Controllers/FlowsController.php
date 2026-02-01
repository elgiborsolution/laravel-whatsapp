<?php

namespace ESolution\WhatsApp\Http\Controllers;

use ESolution\WhatsApp\Models\WhatsappAccount;
use ESolution\WhatsApp\Services\TechProvider\FlowsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FlowsController extends Controller
{
    public function __construct(protected FlowsService $service) {}

    /**
     * List all flows for a WABA.
     */
    public function index(WhatsappAccount $account)
    {
        return response()->json($this->service->listFlows($account));
    }

    /**
     * Create a new WhatsApp Flow.
     */
    public function store(Request $request, WhatsappAccount $account)
    {
        $request->validate([
            'name' => 'required|string',
            'categories' => 'required|array',
        ]);

        return response()->json($this->service->createFlow($account, $request->name, $request->categories));
    }

    /**
     * Update a flow (upload JSON asset).
     */
    public function updateAsset(Request $request, WhatsappAccount $account, string $flowId)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $path = $request->file('file')->getRealPath();

        $success = $this->service->updateFlowAsset($account, $flowId, $path);

        return response()->json(['success' => $success]);
    }

    /**
     * Publish a flow.
     */
    public function publish(WhatsappAccount $account, string $flowId)
    {
        $success = $this->service->publishFlow($account, $flowId);

        return response()->json(['success' => $success]);
    }
}
