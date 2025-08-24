<?php

namespace ESolution\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppBroadcastRecipient extends Model
{
    protected $fillable = [
        'whatsapp_broadcast_id','to','status','wa_message_id','error_code','error_title','error_details',
        'sent_at','delivered_at','read_at'
    ];
    protected $casts = ['sent_at'=>'datetime','delivered_at'=>'datetime','read_at'=>'datetime'];
}
