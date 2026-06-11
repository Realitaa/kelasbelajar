<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                ->constrained('classroom_modules')
                ->cascadeOnDelete();
            $table->morphs('object');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->index([
                'module_id',
                'position',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_objects');
    }
};
