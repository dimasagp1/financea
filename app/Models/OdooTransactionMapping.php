<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooTransactionMapping extends Model
{
    protected $fillable = [
        'odoo_move_line_id',
        'odoo_coa_mapping_id',
        'odoo_coa_mapping_target_id',
        'department_id',
        'budget_category_id',
        'expense_id',
    ];

    public function coaMapping()
    {
        return $this->belongsTo(OdooCoaMapping::class, 'odoo_coa_mapping_id');
    }

    public function target()
    {
        return $this->belongsTo(OdooCoaMappingTarget::class, 'odoo_coa_mapping_target_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetCategory()
    {
        return $this->belongsTo(BudgetCategory::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
