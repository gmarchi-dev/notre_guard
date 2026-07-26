<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "key_items" e não "keys": KEY é palavra reservada no MySQL e a tabela
        // precisaria de aspas em toda consulta crua.
        Schema::create('key_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);              // número do gancho no quadro
            $table->string('name');                  // o que a chave abre
            $table->string('storage_location')->nullable(); // quadro, armário, cofre
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Uma linha por cópia física: a portaria pendura cada uma no seu
            // gancho, então "12A" e "12B" são chaves distintas. Contar cópias
            // num inteiro esconderia qual delas está fora.
            $table->unique(['unit_id', 'code']);
        });

        Schema::create('key_holders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 20)->default('staff'); // staff | teacher | contractor | other
            $table->string('department')->nullable();
            $table->string('document', 30)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('name');
        });

        Schema::create('key_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('key_holder_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();

            // Quem entregou e quem recebeu de volta: são os vigilantes de plantão,
            // e podem ser pessoas diferentes quando a chave atravessa o turno.
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('released_at');
            $table->timestamp('due_at');            // prazo informado na retirada
            $table->timestamp('returned_at')->nullable();

            $table->string('purpose')->nullable();  // motivo da retirada
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['unit_id', 'released_at']);
            $table->index(['returned_at', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_loans');
        Schema::dropIfExists('key_holders');
        Schema::dropIfExists('key_items');
    }
};
