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
        Schema::table('global_monthly_budgets', function (Blueprint $table) {
            // Drop the foreign key constraint first, to allow dropping the index in MySQL
            $table->dropForeign(['fiscal_year_id']);

            // Drop the old unique constraint
            $table->dropUnique(['fiscal_year_id', 'month', 'year']);
            
            // Add the new column
            $table->enum('type', ['actual', 'forecast'])->default('actual')->after('amount');
            
            // Add the new unique constraint including type
            $table->unique(['fiscal_year_id', 'month', 'year', 'type'], 'gmb_fy_month_year_type_unique');

            // Re-add the foreign key constraint
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('global_monthly_budgets', function (Blueprint $table) {
            $table->dropForeign(['fiscal_year_id']);
            $table->dropUnique('gmb_fy_month_year_type_unique');
            
            $table->dropColumn('type');
            
            $table->unique(['fiscal_year_id', 'month', 'year']);
            $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->cascadeOnDelete();
        });
    }
};
