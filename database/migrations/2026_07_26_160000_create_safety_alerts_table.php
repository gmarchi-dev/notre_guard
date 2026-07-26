<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pânico e inatividade numa tabela só: têm origens diferentes, mas o
        // mesmo ciclo de vida (aberto → reconhecido → encerrado), a mesma tela e
        // o mesmo aviso à supervisão. Separar duplicaria tudo isso.
        Schema::create('safety_alerts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // pânico nasce no aparelho; inatividade, no servidor
            $table->string('kind', 20);     // panic | inactivity

            $table->foreignId('security_guard_id')->constrained()->cascadeOnDelete();
            // Nullable de propósito: um pedido de socorro sem unidade definida
            // ainda é um pedido de socorro. Falhar a gravação por causa disso
            // perderia o acionamento.
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patrol_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('occurred_at');
            $table->timestamp('received_at');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('accuracy_m')->nullable();

            // Minutos sem registro que dispararam o alerta de inatividade.
            $table->unsignedSmallInteger('silence_minutes')->nullable();

            $table->string('status', 20)->default('open'); // open | acknowledged | resolved | false_alarm
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('notes')->nullable();

            $table->string('device_id', 64)->nullable();
            $table->timestamps();

            $table->index(['status', 'occurred_at']);
            $table->index(['unit_id', 'occurred_at']);
            // Um alerta de inatividade por ronda: sem isto o agendador criaria
            // um novo a cada execução enquanto o silêncio durasse.
            $table->unique(['kind', 'patrol_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_alerts');
    }
};
