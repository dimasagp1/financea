<?php
// app/Models/MonthlyBudget.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBudget extends Model
{
    protected $fillable = [
        'department_id', 'month', 'amount', 'is_overridden', 'notes', 'created_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_overridden' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMonthNameAttribute()
    {
        return date('F Y', strtotime($this->month . '-01'));
    }

    public function getTotalExpensesAttribute()
    {
        $startDate = $this->month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        return Expense::where('department_id', $this->department_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    public function getUtilizationPercentageAttribute()
    {
        if ($this->amount <= 0) {
            return 0;
        }
        
        return round(($this->total_expenses / $this->amount) * 100, 2);
    }
}