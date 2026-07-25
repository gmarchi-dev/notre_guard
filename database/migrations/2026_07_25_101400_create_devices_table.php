<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aparelho corporativo. Um dispositivo por posto/turno: o vigilante faz
        // login nele, e o gestor pode revogar o acesso do aparelho perdido sem
        // mexer na conta de ninguém.
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 64)->unique(); // gerado na primeira abertura da PWA
            $table->string('name')->nullable();        // etiqueta física do aparelho
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('last_security_guard_id')->nullable()->constrained('security_guards')->nullOnDelete();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
