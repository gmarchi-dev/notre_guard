<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_guard_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_id', 64)->nullable();
            $table->unsignedSmallInteger('items_total')->default(0);
            $table->unsignedSmallInteger('items_accepted')->default(0);
            $table->unsignedSmallInteger('items_duplicated')->default(0);
            $table->unsignedSmallInteger('items_failed')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_batches');
    }
};
