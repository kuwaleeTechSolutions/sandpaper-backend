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
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('test_id')
                  ->constrained()
                  ->cascadeOnDelete();
        
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
        
            $table->integer('attempt_no')->default(1);
        
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
        
            $table->integer('marks_obtained')->nullable();
        
            $table->enum('status', [
                'in_progress',
                'submitted',
                'evaluated',
                'expired'
            ])->default('in_progress');
        
            $table->timestamps();
        
            $table->unique(['test_id','user_id','attempt_no']);
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
