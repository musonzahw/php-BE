<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone', 30);
            $table->string('employee_number')->unique();
            $table->string('gender', 20);
            $table->date('date_of_birth');
            $table->string('national_id')->unique();
            $table->text('address');
            $table->date('date_joined');
            $table->string('qualification');
            $table->string('specialization');
            $table->string('employment_type', 30);
            $table->decimal('salary', 12, 2);
            $table->string('profile_photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
