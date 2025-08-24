<?php

namespace ESolution\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ESolution\WhatsApp\Models\{WhatsAppAccount, WhatsAppMessage};
use ESolution\WhatsApp\Jobs\SendMessageJob;

class MessageController extends Controller
{
    public function send(Request $r)
    {
        $data = $r->validate([
            'whatsapp_account_id' => 'nullable|integer',
            'to' => 'required|string',
            'type' => 'required|string',
            'payload' => 'required|array'
        ]);

        $acc = WhatsAppAccount::resolve($data['whatsapp_account_id'] ?? null);

        $msg = WhatsAppMessage::create([
            'whatsapp_account_id' => $acc->id ?: ($data['whatsapp_account_id'] ?? null),
            'to' => $data['to'],
            'type' => $data['type'],
            'payload' => $data['payload'],
            'status' => 'queued'
        ]);

        dispatch((new SendMessageJob($msg->id))->onConnection(config('whatsapp.queue')));

        return response()->json(['queued'=>true,'message_id'=>$msg->id]);
    }
}
