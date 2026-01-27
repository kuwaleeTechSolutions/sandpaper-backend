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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses');
            $table->string('name'); // Morning Batch
            $table->string('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('total_seats')->default(0);
            $table->text('batch_image')->nullable();
            $table->string('base_price')->nullable();
            $table->string('tax')->nullable();
            $table->json('additional_field')->nullable();
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
        Schema::dropIfExists('batches');
    }
};
