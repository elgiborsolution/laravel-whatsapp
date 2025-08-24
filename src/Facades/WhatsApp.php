<?php

namespace ESolution\WhatsApp\Facades;

use Illuminate\Support\Facades\Facade;
use ESolution\WhatsApp\Services\WhatsAppService;

class WhatsApp extends Facade
{
    protected static function getFacadeAccessor() { return WhatsAppService::class; }
}
