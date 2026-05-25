<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify budget_categories table
        Schema::table('budget_categories', function (Blueprint $table) {
            $table->decimal('budget_ratio_percent', 5, 2)->default(0)->after('name');
            $table->decimal('allocated_amount', 15, 2)->default(0)->after('budget_ratio_percent');
        });

        // 2. Add budget_category_id to expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('budget_category_id')->nullable()->after('id')->constrained('budget_categories');
        });

        // 3. Migrate existing data from items to categories
        // We use raw SQL for performance and simplicity in migration
        DB::statement("
            UPDATE expenses e
            JOIN budget_items i ON e.item_id = i.id
            SET e.budget_category_id = i.category_id
        ");

        // 4. Cleanup expenses table
        // First drop foreign key constraint on item_id if exists
        // Note: The constraint name is usually expenses_item_id_foreign, but we should be careful.
        // Laravel's constrained() method uses table_column_foreign convention.
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['item_id']);
            $table->dropColumn('item_id');
        });

        // 5. Make budget_category_id required after migration
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('budget_category_id')->nullable(false)->change();
        });

        // 6. Drop budget_items table
        Schema::dropIfExists('budget_items');
    }

    public function down(): void
    {
        // Recreating budget_items table
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

        // Reverting expenses table changes
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('item_id')->nullable()->after('id')->constrained('budget_items');
        });

        // We cannot easily revert data migration because the specific item mapping is lost
        // Logic would be complex: create dummy item for each category?
        // Ideally down() should just restore structure. Data loss on down is expected here for items.

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['budget_category_id']);
            $table->dropColumn('budget_category_id');
        });

        Schema::table('budget_categories', function (Blueprint $table) {
            $table->dropColumn(['budget_ratio_percent', 'allocated_amount']);
        });
    }
};
