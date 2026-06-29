<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            $table->string('virtual_tour_url', 500)->nullable()->after('content');
        });

        Schema::table('re_projects', function (Blueprint $table) {
            $table->string('virtual_tour_url', 500)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('re_properties', function (Blueprint $table) {
            $table->dropColumn('virtual_tour_url');
        });

        Schema::table('re_projects', function (Blueprint $table) {
            $table->dropColumn('virtual_tour_url');
        });
    }
};
