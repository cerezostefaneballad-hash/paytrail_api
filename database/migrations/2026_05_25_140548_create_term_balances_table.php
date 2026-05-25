<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('academic_year');
            $table->string('semester');           // "1st sem" or "2nd sem"
            $table->decimal('total_payable', 10, 2);
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->decimal('outstanding_balance', 10, 2)->storedAs('total_payable - total_paid');
            $table->json('payment_breakdown')->nullable();
            $table->string('status')->default('Unpaid');
            $table->timestamps();
            
            $table->unique(['student_id', 'academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_balances');
    }
};