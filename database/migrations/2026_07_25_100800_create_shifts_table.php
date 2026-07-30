<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // gerado no dispositivo - chave de idempotência
            $table->foreignId('security_guard_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();

            $table->timestamp('started_at');            // occurred_at da assunção
            $table->timestamp('started_received_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('ended_received_at')->nullable();

            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->unsignedInteger('start_accuracy_m')->nullable();

            $table->string('status', 20)->default('open'); // open | closed
            $table->text('handover_notes')->nullable();    // passagem de serviço
            $table->json('deviations')->nullable();

            $table->string('chain_hash', 64)->nullable();  // selo de integridade no fechamento
            $table->string('device_id', 64)->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'started_at']);
            $table->index(['security_guard_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
