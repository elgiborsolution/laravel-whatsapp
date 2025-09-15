<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('whatsapp_account_id')->nullable()->constrained('whatsapp_accounts')->cascadeOnDelete();
            $t->string('to')->index();
            $t->string('type')->index();
            $t->json('payload');
            $t->string('wa_message_id')->nullable()->index();
            $t->string('status')->default('queued')->index();
            $t->string('error_code')->nullable();
            $t->string('error_title')->nullable();
            $t->text('error_details')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('whatsapp_messages'); }
};
