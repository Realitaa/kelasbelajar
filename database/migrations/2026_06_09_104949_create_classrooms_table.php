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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('unique_code')->unique();
            $table->text('description');
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index('educator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
