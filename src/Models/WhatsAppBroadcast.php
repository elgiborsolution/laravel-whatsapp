<?php

namespace ESolution\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappBroadcast extends Model
{
    protected $fillable = [
        'whatsapp_account_id','name','type','payload','scheduled_at','status','chunk_size','rate_per_min'
    ];
    protected $casts = ['payload'=>'array','scheduled_at'=>'datetime'];
}
