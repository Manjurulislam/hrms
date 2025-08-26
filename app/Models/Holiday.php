<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'description',
        'day_at',
        'company_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'day_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
