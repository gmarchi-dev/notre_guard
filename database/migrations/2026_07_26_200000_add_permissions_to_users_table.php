<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Permissões nomeadas, concedidas uma a uma. Coluna JSON em vez de
            // tabela de permissões: são poucas e fixas no código, e adicionar
            // uma nova passa a ser uma constante mais uma caixa de seleção, sem
            // migração nem pacote de terceiros.
            $table->json('permissions')->nullable()->after('role');
        });

        // Sem concessão automática de propósito: permissão que se ganha por
        // migração é permissão que ninguém decidiu dar. O administrador concede
        // em Configuração → Usuários.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
