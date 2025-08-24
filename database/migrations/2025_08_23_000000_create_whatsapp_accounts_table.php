<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->string('waba_id')->nullable()->index();
            $t->string('phone_number_id')->index();
            $t->text('access_token');
            $t->boolean('is_default')->default(false)->index();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('whatsapp_accounts'); }
};
