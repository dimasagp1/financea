<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_coa_mappings', function (Blueprint $table) {
            $table->id();
            $table->integer('odoo_account_id')->unique();
            $table->string('odoo_account_code');
            $table->string('odoo_account_name');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('budget_category_id')->nullable()->constrained('budget_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_coa_mappings');
    }
};
