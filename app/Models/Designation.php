<?php

namespace App\Models;

use App\Enums\DesignationLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'parent_id',
        'company_id',
        'level',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'level'  => DesignationLevel::class,
    ];

    protected $appends = ['level_label'];

    public function getLevelLabelAttribute(): string
    {
        return $this->level ? $this->level->label() : '-';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Designation::class, 'parent_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
