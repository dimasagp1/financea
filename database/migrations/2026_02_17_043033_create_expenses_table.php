<?php
// database/migrations/2024_01_01_000007_create_expenses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('item_id')->constrained('budget_items');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description');
            $table->string('reference')->nullable();
            $table->string('odoo_move_line_id')->unique()->nullable();
            $table->json('odoo_data')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['department_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};