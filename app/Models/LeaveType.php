<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_per_year',
        'company_id',
        'approval_workflow_id',
        'status',
    ];

    protected $casts = [
        'status'       => 'boolean',
        'max_per_year' => 'integer',
    ];

    /** Active leave types for a company, ordered by name. */
    public function scopeActiveForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId)
            ->where('status', true)
            ->orderBy('name');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function approvalWorkflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class);
    }
}
