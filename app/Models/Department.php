<?php
// app/Models/Department.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'code',
        'name',
        'budget_ratio_percent',
        'yearly_allocated_amount',
        'odoo_analytic_id',
        'fiscal_year_id',
        'head_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'budget_ratio_percent' => 'decimal:2',
        'yearly_allocated_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function budgetCategories()
    {
        return $this->hasMany(BudgetCategory::class);
    }

    public function costCategories()
    {
        return $this->hasMany(BudgetCategory::class);
    }

    public function monthlyBudgets()
    {
        return $this->hasMany(MonthlyBudget::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function getTotalExpensesAttribute()
    {
        return $this->expenses()->sum('amount');
    }

    public function getBudgetUtilizationPercentageAttribute()
    {
        if ($this->yearly_allocated_amount <= 0) {
            return 0;
        }

        $percentage = ($this->total_expenses / $this->yearly_allocated_amount) * 100;
        return round($percentage, 2);
    }

    public function getUtilizationStatusAttribute()
    {
        $percentage = $this->budget_utilization_percentage;

        if ($percentage > 100) {
            return 'danger';
        } elseif ($percentage <= 20) {
            return 'warning';
        } elseif ($percentage <= 80) {
            return 'safe';
        } else {
            return 'warning';
        }
    }

    public function recalculateYearlyAllocation($globalBudget)
    {
        $this->yearly_allocated_amount = ($globalBudget * $this->budget_ratio_percent) / 100;
        $this->save();

        // Reset monthly budgets to default distribution
        $this->resetMonthlyBudgetsToDefault();

        return $this->yearly_allocated_amount;
    }

    protected function resetMonthlyBudgetsToDefault()
    {
        $monthlyAmount = $this->yearly_allocated_amount / 12;

        for ($month = 1; $month <= 12; $month++) {
            $monthStr = date('Y-m', strtotime($this->fiscalYear->year . '-' . $month . '-01'));

            $existing = MonthlyBudget::where('department_id', $this->id)
                ->where('month', $monthStr)
                ->first();

            if ($existing && $existing->is_overridden) {
                continue;
            }

            MonthlyBudget::updateOrCreate(
                [
                    'department_id' => $this->id,
                    'month' => $monthStr
                ],
                [
                    'amount' => $monthlyAmount,
                    'is_overridden' => false
                ]
            );
        }
    }
}