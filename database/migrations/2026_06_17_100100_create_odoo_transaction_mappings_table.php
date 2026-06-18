<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores the per-transaction assignment done by superadmin in Croscheck page
        // Only used when a COA has more than 1 mapping target
        Schema::create('odoo_transaction_mappings', function (Blueprint $table) {
            $table->id();

            // The Odoo account.move.line ID (string form of the integer ID)
            $table->string('odoo_move_line_id')->unique();

            // The parent COA mapping header
            $table->foreignId('odoo_coa_mapping_id')->constrained('odoo_coa_mappings')->onDelete('cascade');

            // The specific target chosen for this transaction
            $table->foreignId('odoo_coa_mapping_target_id')->constrained('odoo_coa_mapping_targets')->onDelete('cascade');

            // Denormalized for fast sync access
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('budget_category_id')->nullable()->constrained('budget_categories');

            // After sync: the resulting expense record
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_transaction_mappings');
    }
};
