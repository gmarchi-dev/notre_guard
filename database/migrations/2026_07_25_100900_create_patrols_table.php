<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrols', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('started_received_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('ended_received_at')->nullable();

            $table->string('status', 20)->default('in_progress'); // in_progress | completed | abandoned
            $table->unsignedSmallInteger('expected_checkpoints')->default(0);
            $table->unsignedSmallInteger('scanned_checkpoints')->default(0);
            $table->json('deviations')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'started_at']);
        });

        Schema::create('patrol_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patrol_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checkpoint_id')->constrained()->cascadeOnDelete();

            $table->timestamp('occurred_at');  // relógio do dispositivo
            $table->timestamp('received_at');  // relógio do servidor

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('accuracy_m')->nullable();
            $table->unsignedInteger('distance_m')->nullable(); // distância ao checkpoint esperado

            $table->string('method', 10)->default('qr');   // qr | nfc | manual
            $table->string('outcome', 20)->default('scanned'); // scanned | skipped
            $table->text('justification')->nullable();     // obrigatória quando skipped
            $table->json('deviations')->nullable();        // out_of_radius, out_of_window, clock_skew...
            $table->timestamps();

            $table->index(['patrol_id', 'occurred_at']);
            $table->index(['checkpoint_id', 'occurred_at']);
        });

        Schema::create('checklist_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('patrol_scan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->string('answer', 20); // conforming | nonconforming | not_applicable
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['patrol_scan_id', 'checklist_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_responses');
        Schema::dropIfExists('patrol_scans');
        Schema::dropIfExists('patrols');
    }
};
