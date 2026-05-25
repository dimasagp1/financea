<?php
// database/migrations/2024_01_01_000003_create_departments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('budget_ratio_percent', 5, 2)->default(0);
            $table->decimal('yearly_allocated_amount', 15, 2)->default(0);
            $table->string('odoo_analytic_id')->nullable();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->string('head_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};