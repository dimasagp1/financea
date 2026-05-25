<?php
// database/migrations/2024_01_01_000004_create_budget_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['code', 'department_id', 'fiscal_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
    }
};