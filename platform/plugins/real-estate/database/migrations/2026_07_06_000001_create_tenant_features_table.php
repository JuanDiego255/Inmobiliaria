<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('re_tenant_features')) {
            Schema::create('re_tenant_features', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id', 50)->unique();
                $table->boolean('photos_enabled')->default(false);
                $table->boolean('interactive_buttons_enabled')->default(false);
                $table->boolean('dashboard_enabled')->default(false);
                $table->boolean('pdf_documents_enabled')->default(false);
                $table->string('plan_name', 50)->nullable();
                $table->timestamp('plan_started_at')->nullable();
                $table->timestamp('plan_expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('re_tenant_features');
    }
};
