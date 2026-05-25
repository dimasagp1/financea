<?php

namespace App\Imports;

use App\Models\BudgetCategory;
use App\Models\MonthlyBudget;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class ForecastImport implements ToCollection, WithHeadingRow
{
    protected $departmentId;
    protected $month;
    protected $userId;

    public function __construct($departmentId, $month, $userId)
    {
        $this->departmentId = $departmentId;
        $this->month = $month;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $totalNominal = $rows->sum('nominal_peramalan');

        if ($totalNominal <= 0) {
            throw new \Exception('Total nominal peramalan harus lebih dari 0.');
        }

        DB::transaction(function () use ($rows, $totalNominal) {
            // 1. Update Monthly Budget (Total Forecast)
            MonthlyBudget::updateOrCreate(
                [
                    'department_id' => $this->departmentId,
                    'month' => $this->month,
                ],
                [
                    'amount' => $totalNominal,
                    'is_overridden' => true,
                    'created_by' => $this->userId,
                ]
            );

            // 2. Update Category Ratios
            foreach ($rows as $row) {
                $categoryId = $row['id_kategori'] ?? null;
                $nominal = (float) ($row['nominal_peramalan'] ?? 0);

                if ($categoryId) {
                    $category = BudgetCategory::find($categoryId);
                    if ($category && $category->department_id == $this->departmentId) {
                        // Recalculate ratio based on new total
                        $newRatio = ($nominal / $totalNominal) * 100;
                        $category->update([
                            'budget_ratio_percent' => $newRatio
                        ]);
                    }
                }
            }
        });
    }
}
