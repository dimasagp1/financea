<?php
// app/Services/BudgetCalculationService.php

namespace App\Services;

use App\Models\FiscalYear;
use App\Models\Department;
use App\Models\MonthlyBudget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetCalculationService
{
    /**
     * Recalculate all department budgets based on global budget and ratios
     */
    public function recalculateAllDepartments(FiscalYear $fiscalYear)
    {
        DB::beginTransaction();
        
        try {
            $totalRatio = Department::where('fiscal_year_id', $fiscalYear->id)
                ->sum('budget_ratio_percent');

            if ($totalRatio > 100) {
                throw new \Exception('Total budget ratio exceeds 100%');
            }

            $departments = Department::where('fiscal_year_id', $fiscalYear->id)->get();
            
            foreach ($departments as $department) {
                $this->recalculateDepartment($department, $fiscalYear->global_budget_amount);
            }

            DB::commit();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Budget recalculation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recalculate single department budget
     */
    public function recalculateDepartment(Department $department, $globalBudget)
    {
        $yearlyAllocation = ($globalBudget * $department->budget_ratio_percent) / 100;
        $department->yearly_allocated_amount = $yearlyAllocation;
        $department->save();

        $this->resetMonthlyBudgets($department);

        return true;
    }

    /**
     * Reset monthly budgets to default distribution
     */
    public function resetMonthlyBudgets(Department $department)
    {
        $monthlyAmount = $department->yearly_allocated_amount / 12;
        
        for ($month = 1; $month <= 12; $month++) {
            $monthStr = date('Y-m', strtotime($department->fiscalYear->year . '-' . $month . '-01'));
            
            $existing = MonthlyBudget::where('department_id', $department->id)
                ->where('month', $monthStr)
                ->first();

            if ($existing && $existing->is_overridden) {
                continue;
            }

            MonthlyBudget::updateOrCreate(
                [
                    'department_id' => $department->id,
                    'month' => $monthStr
                ],
                [
                    'amount' => $monthlyAmount,
                    'is_overridden' => false,
                    'notes' => 'Auto-calculated from yearly budget'
                ]
            );
        }
    }

    public function recalculateBudget(Department $department, float $newRatio): Department
    {
        $fiscalYear = $department->fiscalYear;

        if (!$fiscalYear) {
            throw new \InvalidArgumentException('Department tidak memiliki fiscal year aktif.');
        }

        $currentRatio = Department::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('id', '!=', $department->id)
            ->sum('budget_ratio_percent');

        if (($currentRatio + $newRatio) > 100) {
            throw new \InvalidArgumentException('Total rasio seluruh departemen tidak boleh melebihi 100%.');
        }

        DB::transaction(function () use ($department, $newRatio, $fiscalYear) {
            $department->budget_ratio_percent = $newRatio;
            $department->yearly_allocated_amount = ($fiscalYear->global_budget_amount * $newRatio) / 100;
            $department->save();

            $this->resetMonthlyBudgets($department);
        });

        return $department->refresh();
    }

    /**
     * Update monthly budget manually (override)
     */
    public function updateMonthlyBudget(Department $department, $month, $amount, $userId)
    {
        $monthlyBudget = MonthlyBudget::updateOrCreate(
            [
                'department_id' => $department->id,
                'month' => $month
            ],
            [
                'amount' => $amount,
                'is_overridden' => true,
                'created_by' => $userId,
                'notes' => 'Manual override'
            ]
        );

        return $monthlyBudget;
    }

    /**
     * Validate department ratios
     */
    public function validateDepartmentRatios(FiscalYear $fiscalYear, $excludeDepartmentId = null)
    {
        $query = Department::where('fiscal_year_id', $fiscalYear->id);
        
        if ($excludeDepartmentId) {
            $query->where('id', '!=', $excludeDepartmentId);
        }
        
        $totalRatio = $query->sum('budget_ratio_percent');
        
        return [
            'total' => $totalRatio,
            'remaining' => 100 - $totalRatio,
            'is_valid' => $totalRatio <= 100
        ];
    }
}