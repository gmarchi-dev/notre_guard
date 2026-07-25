<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // gerado no dispositivo; referenciado pelo evento antes do upload
            $table->nullableMorphs('attachable');
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->string('sha256', 64)->nullable();

            $table->timestamp('captured_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('status', 20)->default('pending'); // pending | stored
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
