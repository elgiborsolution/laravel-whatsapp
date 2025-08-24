<?php

namespace ESolution\WhatsApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
