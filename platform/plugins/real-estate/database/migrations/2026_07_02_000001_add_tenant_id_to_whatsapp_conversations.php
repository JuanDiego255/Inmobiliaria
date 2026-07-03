<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('re_whatsapp_conversations') && ! Schema::hasColumn('re_whatsapp_conversations', 'tenant_id')) {
            Schema::table('re_whatsapp_conversations', function (Blueprint $table) {
                $table->string('tenant_id', 50)->nullable()->after('phone')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('re_whatsapp_conversations') && Schema::hasColumn('re_whatsapp_conversations', 'tenant_id')) {
            Schema::table('re_whatsapp_conversations', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
