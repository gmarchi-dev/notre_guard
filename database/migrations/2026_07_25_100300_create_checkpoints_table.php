<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('qr_token', 32)->unique();
            $table->string('nfc_uid', 64)->nullable()->unique();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_m')->default(50);
            $table->text('instruction')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['unit_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkpoints');
    }
};
