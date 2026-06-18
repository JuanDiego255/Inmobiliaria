<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('re_crm_leads')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('re_properties')->nullOnDelete();
            $table->string('priority', 20)->default('medium');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('assigned_to');
            $table->index('status');
            $table->index('due_date');
            $table->index('priority');
        });

        Schema::create('re_crm_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->timestamp('remind_at');
            $table->foreignId('lead_id')->nullable()->constrained('re_crm_leads')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_dismissed', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_crm_reminders');
        Schema::dropIfExists('re_crm_tasks');
    }
};
