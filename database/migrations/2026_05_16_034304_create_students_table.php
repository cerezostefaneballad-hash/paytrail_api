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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('year_level');               // '1','2','3','4'

            $table->decimal('first_amount', 10, 2)->default(0);
            $table->date('first_date')->nullable();
            $table->decimal('second_amount', 10, 2)->default(0);
            $table->date('second_date')->nullable();
            $table->decimal('third_amount', 10, 2)->default(0);
            $table->date('third_date')->nullable();

            $table->string('semester')->nullable();      // '1st sem' / '2nd sem'
            $table->string('acad_year')->nullable();     // e.g. '2025-2026'
            $table->string('issued_by')->nullable();     // admin username

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
