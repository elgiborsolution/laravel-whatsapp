<?php

namespace ESolution\WhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ESolution\WhatsApp\Models\{WhatsAppAccount, WhatsAppTemplate};
use ESolution\WhatsApp\Services\WhatsAppService;

class TemplateController extends Controller
{
    public function index(Request $r)
    {
        return WhatsAppTemplate::query()
            ->when($r->whatsapp_account_id, fn($q,$id)=>$q->where('whatsapp_account_id',$id))
            ->paginate($r->get('per_page', 25));
    }

    public function store(Request $r, WhatsAppService $svc)
    {
        $data = $r->validate([
            'whatsapp_account_id' => 'nullable|integer',
            'name' => 'required|string',
            'language' => 'required|string',
            'category' => 'nullable|string',
            'components' => 'required|array',
            'sync_to_meta' => 'boolean'
        ]);

        $acc = WhatsAppAccount::resolve($data['whatsapp_account_id'] ?? null);
        $tpl = WhatsAppTemplate::create([
            'whatsapp_account_id' => $acc->id ?: ($data['whatsapp_account_id'] ?? null),
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'] ?? null,
            'status' => 'PENDING',
            'components' => $data['components'],
        ]);

        if (!empty($data['sync_to_meta'])) {
            $svc->createTemplate($acc, [
                'name' => $tpl->name,
                'category' => $tpl->category ?? 'MARKETING',
                'language' => $tpl->language,
                'components' => $tpl->components,
            ]);
        }

        return response()->json($tpl, 201);
    }

    public function show($id) { return WhatsAppTemplate::findOrFail($id); }

    public function update($id, Request $r)
    {
        $tpl = WhatsAppTemplate::findOrFail($id);
        $tpl->update($r->only(['name','language','category','components','status']));
        return $tpl;
    }

    public function destroy($id) {
        $tpl = WhatsAppTemplate::findOrFail($id);
        $tpl->delete();
        return response()->json(['deleted'=>true]);
    }

    public function syncFromMeta($id, WhatsAppService $svc)
    {
        $tpl = WhatsAppTemplate::findOrFail($id);
        $acc = WhatsAppAccount::resolve($tpl->whatsapp_account_id);
        return response()->json(['message'=>'Sync placeholder - filter from $svc->listTemplates()']);
    }
}
