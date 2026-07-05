<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('re_whatsapp_bot_flows')) {
            Schema::create('re_whatsapp_bot_flows', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id', 50)->index();
                $table->string('name');
                $table->text('greeting_message')->nullable();
                $table->json('flow_config');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('re_tenant_mail_configs')) {
            Schema::create('re_tenant_mail_configs', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id', 50)->unique();
                $table->string('mail_driver', 20)->default('smtp');
                $table->string('mail_host')->default('smtp.gmail.com');
                $table->unsignedSmallInteger('mail_port')->default(587);
                $table->string('mail_username')->nullable();
                $table->string('mail_password')->nullable();
                $table->string('mail_encryption', 10)->default('tls');
                $table->string('mail_from_address')->nullable();
                $table->string('mail_from_name')->nullable();
                $table->json('notification_emails')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('re_whatsapp_bot_flows');
        Schema::dropIfExists('re_tenant_mail_configs');
    }
};
