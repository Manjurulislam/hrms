<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    protected $fillable = [
        'title',
        'description',
        'company_id',
        'department_id',
        'created_by',
        'published_at',
        'expired_at',
        'status',
    ];

    protected $casts = [
        'status'       => 'boolean',
        'published_at' => 'date',
        'expired_at'   => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now()->toDateString());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expired_at')
              ->orWhere('expired_at', '>=', now()->toDateString());
        });
    }

    /**
     * Notices an employee may see: active, published, not expired, in their
     * company, and either company-wide or targeted at their department.
     */
    public function scopeVisibleTo(Builder $query, Employee $employee): Builder
    {
        return $query->active()
            ->published()
            ->notExpired()
            ->where('company_id', $employee->company_id)
            ->where(fn ($q) => $q->whereNull('department_id')
                ->orWhere('department_id', $employee->department_id));
    }
}
