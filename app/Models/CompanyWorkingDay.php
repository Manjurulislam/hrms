<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyWorkingDay extends Model
{
    protected $fillable = [
        'company_id',
        'day_of_week',
        'day_label',
        'is_working',
    ];

    protected $casts = [
        'is_working'  => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWorking(Builder $query): void
    {
        $query->where('is_working', true);
    }
}
