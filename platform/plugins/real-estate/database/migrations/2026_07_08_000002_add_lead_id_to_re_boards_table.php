<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('re_boards', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->after('client_id')
                ->constrained('re_crm_leads')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('re_boards', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
