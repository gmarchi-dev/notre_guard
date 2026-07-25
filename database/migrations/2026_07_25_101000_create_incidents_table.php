<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number', 20);           // RO NNN/AAAA
            $table->unsignedSmallInteger('sequence'); // NNN
            $table->unsignedSmallInteger('year');

            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patrol_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('checkpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('incident_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_id')->nullable()->constrained('security_guards')->nullOnDelete();

            $table->timestamp('occurred_at');   // hora do fato
            $table->timestamp('received_at');   // hora do registro no servidor
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('severity', 20)->default('low');          // low | medium | high | critical
            $table->string('classification', 20)->default('prevention'); // prevention | loss
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->json('people_involved')->nullable();

            $table->string('status', 20)->default('registered'); // draft | registered | under_review | closed
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'year', 'sequence']);
            $table->index(['unit_id', 'occurred_at']);
            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
