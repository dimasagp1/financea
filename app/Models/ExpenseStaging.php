<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseStaging extends Model
{
    use HasFactory;

    protected $table = 'expense_stagings';

    protected $fillable = [
        'department_id',
        'budget_category_id',
        'qty',
        'amount',
        'date',
        'description',
        'reference',
        'status',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'date' => 'date',
        'checked_at' => 'datetime',
        'qty' => 'float',
        'amount' => 'float',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetCategory(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
