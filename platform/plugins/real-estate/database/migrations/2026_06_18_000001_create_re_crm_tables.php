<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('re_crm_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('email', 120)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('stage', 60)->default('nuevo');
            $table->string('source', 60)->default('manual');
            $table->decimal('budget_min', 15, 2)->nullable();
            $table->decimal('budget_max', 15, 2)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('re_currencies')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('re_clients')->nullOnDelete();
            $table->foreignId('consult_id')->nullable()->constrained('re_consults')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('re_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->string('lost_reason', 500)->nullable();
            $table->timestamps();

            $table->index('stage');
            $table->index('source');
            $table->index('assigned_agent_id');
            $table->index('created_at');
        });

        Schema::create('re_crm_lead_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('re_crm_leads')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('re_properties')->cascadeOnDelete();
            $table->string('interest_level', 20)->default('medium');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['lead_id', 'property_id']);
        });

        Schema::create('re_crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('re_crm_leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 60);
            $table->text('description');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'type']);
            $table->index('scheduled_at');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_crm_activities');
        Schema::dropIfExists('re_crm_lead_properties');
        Schema::dropIfExists('re_crm_leads');
    }
};
