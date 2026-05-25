<?php

namespace App\Imports;

use App\Models\BudgetCategory;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class OdooExpenseImport implements ToCollection, WithHeadingRow
{
    private $departmentId;
    private $month;
    private $userId;

    public function __construct($departmentId, $month, $userId)
    {
        $this->departmentId = $departmentId;
        $this->month = Carbon::parse($month . '-01');
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Ensure required columns exist
                if (!isset($row['no_akun']) || !isset($row['nominal'])) {
                    continue; // Skip invalid rows
                }

                $accountCode = trim($row['no_akun']);
                $amount = (float) $row['nominal'];

                if (empty($accountCode) || $amount <= 0) {
                    continue;
                }

                // Find category by code and department
                $category = BudgetCategory::where('code', $accountCode)
                    ->where('department_id', $this->departmentId)
                    ->first();

                if (!$category) {
                    continue; // Skip if category not found
                }

                // Add as expense
                Expense::create([
                    'department_id'      => $this->departmentId,
                    'budget_category_id' => $category->id,
                    'amount'             => $amount,
                    'qty'                => 1,
                    'date'               => $this->month->format('Y-m-d'), // Use year and month filter from upload
                    'description'        => 'Imported from Odoo Excel: ' . ($row['nama_akun'] ?? $category->name),
                    'is_synced'          => true,
                    'synced_at'          => now(),
                ]);
            }
        });
    }
}
