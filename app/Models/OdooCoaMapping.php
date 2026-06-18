<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooCoaMapping extends Model
{
    protected $fillable = [
        'odoo_account_id',
        'odoo_account_code',
        'odoo_account_name',
    ];

    /**
     * All mapped targets (dept + category combos) for this COA.
     */
    public function targets()
    {
        return $this->hasMany(OdooCoaMappingTarget::class);
    }

    /**
     * Shortcut: returns the single target when there is exactly one.
     */
    public function singleTarget()
    {
        return $this->hasOne(OdooCoaMappingTarget::class);
    }

    /**
     * Whether this COA has more than one mapping target.
     */
    public function isMultiMapped(): bool
    {
        return $this->targets()->count() > 1;
    }
}
