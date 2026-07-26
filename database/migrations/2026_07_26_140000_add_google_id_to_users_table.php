<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Vínculo com a conta Google, gravado no primeiro login bem-sucedido.
            // O e-mail identifica; o google_id garante que a conta não mudou de
            // titular (e-mail reaproveitado depois de um desligamento).
            $table->string('google_id')->nullable()->unique()->after('unit_id');
            $table->timestamp('google_linked_at')->nullable()->after('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'google_linked_at']);
        });
    }
};
