<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('re_crm_leads', function (Blueprint $table) {
            $table->unsignedSmallInteger('score')->default(0)->after('lost_reason');
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::table('re_crm_leads', function (Blueprint $table) {
            $table->dropIndex(['score']);
            $table->dropColumn('score');
        });
    }
};
