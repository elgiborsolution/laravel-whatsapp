<?php

namespace ESolution\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappAccount extends Model
{
    protected $fillable = ['name','waba_id','phone_number_id','access_token','is_default','meta'];
    protected $casts = ['meta' => 'array', 'is_default' => 'boolean'];

    public static function resolve(?int $id = null): self
    {
        if ($id) return static::findOrFail($id);

        $cfg = config('whatsapp.single_account');
        if (($cfg['enabled'] ?? false) && ($cfg['phone_number_id'] ?? null)) {
            $m = new static([
                'id' => 0,
                'name' => $cfg['name'] ?? 'default',
                'phone_number_id' => $cfg['phone_number_id'],
                'access_token' => $cfg['access_token'],
                'waba_id' => $cfg['waba_id'] ?? null,
                'is_default' => true,
            ]);
            $m->exists = false;
            return $m;
        }
        return static::where('is_default', true)->first()
            ?? static::query()->firstOrFail();
    }
}
