<?php
// app/Models/Expense.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'department_id',
        'budget_category_id',
        'qty',
        'amount',
        'date',
        'description',
        'reference',
        'odoo_move_line_id',
        'odoo_data',
        'is_synced',
        'synced_at',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'amount' => 'decimal:2',
        'date' => 'date',
        'odoo_data' => 'array',
        'is_synced' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetCategory()
    {
        return $this->belongsTo(BudgetCategory::class);
    }
}