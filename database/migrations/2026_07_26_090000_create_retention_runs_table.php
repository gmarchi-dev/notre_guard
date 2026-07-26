<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro das execuções de expurgo. A LGPD exige poder demonstrar o
        // tratamento — inclusive a eliminação. Sem este histórico, "nós
        // apagamos" é afirmação sem prova.
        Schema::create('retention_runs', function (Blueprint $table) {
            $table->id();
            $table->boolean('dry_run')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('policy');   // prazos vigentes na execução
            $table->json('summary');  // o que foi (ou seria) eliminado
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('started_at');
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            // Quando os dados de campo da data são expurgados, o RDO fechado
            // deixa de ser recalculável. Sem esta marca, a verificação de
            // integridade acusaria "registros chegaram depois do fechamento"
            // para todo RDO antigo — um falso alarme permanente.
            $table->timestamp('data_purged_at')->nullable()->after('content_hash');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn('data_purged_at');
        });

        Schema::dropIfExists('retention_runs');
    }
};
