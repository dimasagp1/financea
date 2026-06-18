<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooCoaMappingTarget extends Model
{
    protected $fillable = [
        'odoo_coa_mapping_id',
        'department_id',
        'budget_category_id',
    ];

    public function mapping()
    {
        return $this->belongsTo(OdooCoaMapping::class, 'odoo_coa_mapping_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetCategory()
    {
        return $this->belongsTo(BudgetCategory::class);
    }
}
