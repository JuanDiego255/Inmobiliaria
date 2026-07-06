<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('re_tenant_mail_configs') && ! Schema::hasColumn('re_tenant_mail_configs', 'notification_whatsapp')) {
            Schema::table('re_tenant_mail_configs', function (Blueprint $table) {
                $table->string('notification_whatsapp', 25)->nullable()->after('notification_emails');
                $table->string('whatsapp_template_name', 100)->nullable()->after('notification_whatsapp');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('re_tenant_mail_configs', 'notification_whatsapp')) {
            Schema::table('re_tenant_mail_configs', function (Blueprint $table) {
                $table->dropColumn(['notification_whatsapp', 'whatsapp_template_name']);
            });
        }
    }
};
