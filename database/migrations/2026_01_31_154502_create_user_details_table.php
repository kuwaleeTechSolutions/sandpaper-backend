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
       Schema::create('user_details', function (Blueprint $table)
       {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users');

            $table->string('reg_no',20)->nullable();
            $table->string('gender', 15)->nullable();
            $table->string('alt_mobile', 12)->nullable();
            $table->string('alt_email', 60)->nullable();
            $table->text('address')->nullable();
            $table->string('profile_pic')->nullable(); // path or filename
            $table->string('pincode', 6)->nullable();
            $table->string('qualification')->nullable();
            $table->date('dob')->nullable();
            $table->json('additional_field')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
