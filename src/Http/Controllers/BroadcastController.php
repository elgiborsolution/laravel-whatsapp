<?php

namespace ESolution\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ESolution\WhatsApp\Models\{WhatsAppBroadcast, WhatsAppBroadcastRecipient, WhatsAppAccount};

class BroadcastController extends Controller
{
    public function index(Request $r)
    {
        return WhatsAppBroadcast::query()
            ->when($r->status, fn($q,$s)=>$q->where('status',$s))
            ->paginate($r->get('per_page', 25));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'whatsapp_account_id' => 'nullable|integer',
            'name' => 'required|string',
            'type' => 'required|string',
            'payload' => 'required|array',
            'recipients' => 'required|array',
            'chunk_size' => 'nullable|integer',
            'rate_per_min' => 'nullable|integer',
        ]);

        $acc = WhatsAppAccount::resolve($data['whatsapp_account_id'] ?? null);

        $b = WhatsAppBroadcast::create([
            'whatsapp_account_id' => $acc->id ?: ($data['whatsapp_account_id'] ?? null),
            'name' => $data['name'],
            'type' => $data['type'],
            'payload' => $data['payload'],
            'status' => 'draft',
            'chunk_size' => $data['chunk_size'] ?? config('whatsapp.broadcast.chunk_size'),
            'rate_per_min' => $data['rate_per_min'] ?? config('whatsapp.broadcast.rate_per_min'),
        ]);

        $rows = [];
        foreach ($data['recipients'] as $to) {
            $rows[] = [
                'whatsapp_broadcast_id' => $b->id,
                'to' => $to,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 1000) as $chunk) {
            WhatsAppBroadcastRecipient::insert($chunk);
        }

        return response()->json(['id'=>$b->id,'created'=>true]);
    }

    public function schedule($id, Request $r)
    {
        $b = WhatsAppBroadcast::findOrFail($id);
        $data = $r->validate(['scheduled_at'=>'required|date']);
        $b->scheduled_at = $data['scheduled_at'];
        $b->status = 'scheduled';
        $b->save();
        return response()->json(['scheduled'=>true]);
    }

    public function pause($id) {
        $b = WhatsAppBroadcast::findOrFail($id);
        $b->status = 'paused'; $b->save();
        return ['paused'=>true];
    }
    public function resume($id) {
        $b = WhatsAppBroadcast::findOrFail($id);
        $b->status = 'scheduled'; $b->save();
        return ['resumed'=>true];
    }
}
