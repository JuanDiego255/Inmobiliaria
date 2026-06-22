<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('re_meta_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 60);
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('processed');
        });

        Schema::table('re_crm_leads', function (Blueprint $table) {
            $table->string('meta_lead_id', 100)->nullable()->after('lost_reason');
            $table->string('meta_platform', 60)->nullable()->after('meta_lead_id');

            $table->index('meta_lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_meta_webhook_logs');

        Schema::table('re_crm_leads', function (Blueprint $table) {
            $table->dropIndex(['meta_lead_id']);
            $table->dropColumn(['meta_lead_id', 'meta_platform']);
        });
    }
};
