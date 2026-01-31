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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // APSC Prelims 2026
            $table->text('description')->nullable();
            $table->integer('duration_months')->nullable();
            $table->text('course_image')->nullable();
            $table->json('additional_field')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
