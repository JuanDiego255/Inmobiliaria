<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('re_crm_tasks', function (Blueprint $table) {
            $table->string('google_event_id', 200)->nullable()->after('completed_at');
        });

        Schema::table('re_crm_reminders', function (Blueprint $table) {
            $table->string('google_event_id', 200)->nullable()->after('is_dismissed');
        });
    }

    public function down(): void
    {
        Schema::table('re_crm_tasks', function (Blueprint $table) {
            $table->dropColumn('google_event_id');
        });

        Schema::table('re_crm_reminders', function (Blueprint $table) {
            $table->dropColumn('google_event_id');
        });
    }
};
