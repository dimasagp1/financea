<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GlobalMonthlyBudget extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'fiscal_year_id',
        'month',
        'year',
        'amount',
        'type',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'year' => 'integer',
        'month' => 'integer',
        'type' => 'string',
    ];

    public function scopeActual($query)
    {
        return $query->where('type', 'actual');
    }

    public function scopeForecast($query)
    {
        return $query->where('type', 'forecast');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fiscal_year_id', 'month', 'year', 'amount', 'type', 'notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
