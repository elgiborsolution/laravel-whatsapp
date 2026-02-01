<?php

namespace ESolution\WhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class WhatsappToken extends Model
{
    protected $fillable = [
        'phone',
        'token',
        'type',
        'metadata',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('verified_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Mark token as verified.
     */
    public function markAsVerified(): bool
    {
        return $this->update(['verified_at' => now()]);
    }
}
