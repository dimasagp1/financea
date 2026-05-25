<?php
use Illuminate\Support\Facades\DB;

try {
    echo "Running migration...\n";
    DB::statement("ALTER TABLE global_monthly_budgets DROP INDEX global_monthly_budgets_fiscal_year_id_month_year_unique");
    DB::statement("ALTER TABLE global_monthly_budgets ADD COLUMN type ENUM('actual', 'forecast') NOT NULL DEFAULT 'actual' AFTER amount");
    DB::statement("ALTER TABLE global_monthly_budgets ADD UNIQUE INDEX gmb_fy_month_year_type_unique (fiscal_year_id, month, year, type)");
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
