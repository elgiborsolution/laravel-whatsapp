<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $t->string('name')->index();
            $t->string('language')->default('en_US')->index();
            $t->string('category')->nullable();
            $t->string('status')->default('PENDING');
            $t->json('components');
            $t->timestamps();
            $t->unique(['whatsapp_account_id','name','language']);
        });
    }
    public function down(): void { Schema::dropIfExists('whatsapp_templates'); }
};
