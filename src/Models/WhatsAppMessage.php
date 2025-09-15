<?php

namespace ESolution\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'whatsapp_account_id','to','type','payload','wa_message_id','status',
        'error_code','error_title','error_details','sent_at','delivered_at','read_at'
    ];
    protected $casts = ['payload'=>'array','sent_at'=>'datetime','delivered_at'=>'datetime','read_at'=>'datetime'];
}
