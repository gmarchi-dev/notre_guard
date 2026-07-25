<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->date('report_date');
            $table->string('status', 20)->default('draft'); // draft | closed
            $table->json('summary')->nullable();            // números consolidados no fechamento
            $table->text('notes')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
