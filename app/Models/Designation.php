<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'parent_id',
        'company_id',
        'department_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
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

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'designation_employee')
            ->withTimestamps();
    }
}
