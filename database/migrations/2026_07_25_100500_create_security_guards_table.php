<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "security_guards" e não "guards": o model Guard colidiria com
        // Model::guard() e com o conceito de auth guard do Laravel.
        Schema::create('security_guards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('default_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('registration', 20)->unique();      // matrícula interna
            $table->string('professional_id', 30)->nullable(); // registro profissional (Lei 14.967/2024)
            $table->date('refresher_valid_until')->nullable(); // validade da reciclagem
            $table->string('phone', 20)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_guards');
    }
};
