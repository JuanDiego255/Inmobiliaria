<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('re_whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 25)->index();
            $table->string('direction', 10);
            $table->text('message');
            $table->foreignId('lead_id')->nullable()->constrained('re_crm_leads')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_whatsapp_conversations');
    }
};
