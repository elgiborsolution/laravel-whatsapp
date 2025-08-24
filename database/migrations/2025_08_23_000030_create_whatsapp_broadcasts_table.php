<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_broadcasts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $t->string('name')->index();
            $t->string('type')->index();
            $t->json('payload');
            $t->timestamp('scheduled_at')->nullable()->index();
            $t->string('status')->default('draft')->index();
            $t->unsignedInteger('chunk_size')->default(1000);
            $t->unsignedInteger('rate_per_min')->default(3000);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('whatsapp_broadcasts'); }
};
