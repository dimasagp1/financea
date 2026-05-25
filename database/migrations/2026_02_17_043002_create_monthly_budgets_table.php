<?php
// database/migrations/2024_01_01_000006_create_monthly_budgets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments');
            $table->string('month', 7); // Format: YYYY-MM
            $table->decimal('amount', 15, 2);
            $table->boolean('is_overridden')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->unique(['department_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};