<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_board_properties', function (Blueprint $table) {
            $table->string('property_status', 60)->default('properties')->after('property_id');
        });
    }

    public function down(): void
    {
        Schema::table('re_board_properties', function (Blueprint $table) {
            $table->dropColumn('property_status');
        });
    }
};
