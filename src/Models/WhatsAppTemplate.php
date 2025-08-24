<?php

namespace ESolution\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $fillable = ['whatsapp_account_id','name','language','category','status','components'];
    protected $casts = ['components' => 'array'];
}
