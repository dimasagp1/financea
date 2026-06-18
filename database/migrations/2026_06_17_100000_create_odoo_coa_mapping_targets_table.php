<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new targets table — one COA can map to many dept+category combos
        Schema::create('odoo_coa_mapping_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odoo_coa_mapping_id')->constrained('odoo_coa_mappings')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('budget_category_id')->nullable()->constrained('budget_categories')->onDelete('set null');
            $table->timestamps();

            // One COA mapping can have a specific dept+category combo only once
            $table->unique(['odoo_coa_mapping_id', 'department_id', 'budget_category_id'], 'unique_coa_dept_cat');
        });

        // 2. Migrate existing single mappings from odoo_coa_mappings into targets table
        $existing = DB::table('odoo_coa_mappings')
            ->whereNotNull('department_id')
            ->get();

        foreach ($existing as $row) {
            DB::table('odoo_coa_mapping_targets')->insertOrIgnore([
                'odoo_coa_mapping_id' => $row->id,
                'department_id'       => $row->department_id,
                'budget_category_id'  => $row->budget_category_id,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        // 3. Drop the old single-mapping columns from odoo_coa_mappings (now in targets)
        Schema::table('odoo_coa_mappings', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['budget_category_id']);
            $table->dropColumn(['department_id', 'budget_category_id']);
        });
    }

    public function down(): void
    {
        // Re-add columns to odoo_coa_mappings
        Schema::table('odoo_coa_mappings', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('budget_category_id')->nullable()->constrained('budget_categories')->onDelete('cascade');
        });

        // Migrate data back (only the first target per COA)
        $targets = DB::table('odoo_coa_mapping_targets')->get();
        foreach ($targets as $t) {
            DB::table('odoo_coa_mappings')
                ->where('id', $t->odoo_coa_mapping_id)
                ->update([
                    'department_id'      => $t->department_id,
                    'budget_category_id' => $t->budget_category_id,
                ]);
        }

        Schema::dropIfExists('odoo_coa_mapping_targets');
    }
};
