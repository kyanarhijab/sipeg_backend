<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique(); 
            $table->string('name');                    
            
            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->cascadeOnDelete();

            $table->string('position');                
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};