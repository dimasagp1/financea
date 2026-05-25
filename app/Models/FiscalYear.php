<?php
// app/Models/FiscalYear.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $fillable = [
        'year',
        'global_budget_amount',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'global_budget_amount' => 'decimal:2',
    ];

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function budgetCategories()
    {
        return $this->hasMany(BudgetCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function globalMonthlyBudgets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GlobalMonthlyBudget::class);
    }
}