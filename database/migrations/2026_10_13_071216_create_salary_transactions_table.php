<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique(); 
            
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->cascadeOnDelete();

            $table->unsignedTinyInteger('month'); 
            $table->year('year');                 
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('allowance', 15, 2)->default(0);
            $table->decimal('deduction', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2);
            $table->enum('status', ['draft', 'paid'])->default('draft');
            $table->timestamps();

            
            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_transactions');
    }
};