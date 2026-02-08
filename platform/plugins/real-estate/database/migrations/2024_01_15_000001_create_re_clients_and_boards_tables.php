<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('re_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('email', 120)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->text('notes')->nullable();
            $table->integer('age')->unsigned()->nullable();
            $table->string('status', 60)->default('active');
            $table->timestamps();
        });

        Schema::create('re_boards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('client_id')->constrained('re_clients')->cascadeOnDelete();
            $table->string('token', 60)->unique();
            $table->string('status', 60)->default('active');
            $table->timestamps();
        });

        Schema::create('re_board_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('re_boards')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('re_properties')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['board_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_board_properties');
        Schema::dropIfExists('re_boards');
        Schema::dropIfExists('re_clients');
    }
};
