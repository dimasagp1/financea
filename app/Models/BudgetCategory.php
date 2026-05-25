<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'budget_ratio_percent',
        'allocated_amount',
        'department_id',
        'fiscal_year_id',
        'is_active',
    ];

    protected $casts = [
        'budget_ratio_percent' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'budget_category_id');
    }

    public function getTotalExpensesAttribute()
    {
        return $this->expenses()->sum('amount');
    }
}
