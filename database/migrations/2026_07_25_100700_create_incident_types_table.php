<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('incident_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('default_classification', 20)->nullable(); // prevention | loss
            $table->string('default_severity', 20)->nullable();       // low | medium | high | critical
            $table->boolean('notify_supervision')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_types');
    }
};
