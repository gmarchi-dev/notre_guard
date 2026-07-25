<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('photo_required_when_nonconforming')->default(true);
            $table->timestamps();
        });

        // Um checkpoint pode exigir um checklist específico na passagem.
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->foreignId('checklist_template_id')
                ->nullable()
                ->after('instruction')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('checkpoints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checklist_template_id');
        });

        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklist_templates');
    }
};
