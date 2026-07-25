<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "patrol_routes" e não "routes": o model Route colidiria com a facade Route.
        Schema::create('patrol_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('ordered')->default(true);
            $table->unsignedSmallInteger('expected_duration_min')->default(30);
            $table->unsignedSmallInteger('tolerance_min')->default(15);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('patrol_route_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('required')->default(true);
            $table->timestamps();

            $table->unique(['patrol_route_id', 'checkpoint_id']);
        });

        Schema::create('patrol_route_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_route_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->time('window_start');
            $table->time('window_end');
            $table->json('weekdays'); // [0..6], 0 = domingo
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrol_route_schedules');
        Schema::dropIfExists('patrol_route_checkpoints');
        Schema::dropIfExists('patrol_routes');
    }
};
