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
        Schema::create('expense_stagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('budget_category_id')->constrained('budget_categories')->onDelete('cascade');
            $table->decimal('qty', 15, 2)->default(1);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('reference')->unique(); // No duplicate PR references
            $table->enum('status', ['pending', 'bon', 'ignored'])->default('pending');
            $table->foreignId('checked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_stagings');
    }
};
