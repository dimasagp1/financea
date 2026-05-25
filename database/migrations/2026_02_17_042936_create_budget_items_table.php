<?php
// database/migrations/2024_01_01_000005_create_budget_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->foreignId('category_id')->constrained('budget_categories');
            $table->string('odoo_account_code')->nullable();
            $table->decimal('planned_amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['code', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};