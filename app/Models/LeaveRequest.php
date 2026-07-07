<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use SoftDeletes;

    /** The given employee's own leave requests, newest first. */
    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId)->latest();
    }

    /** Leave requests currently awaiting action from the given approver. */
    public function scopeAwaitingApprover(Builder $query, int $employeeId): Builder
    {
        return $query->where('current_approver_id', $employeeId)->latest();
    }

    protected $fillable = [
        'title',
        'notes',
        'total_days',
        'company_id',
        'employee_id',
        'leave_type_id',
        'current_approver_id',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at'   => 'date',
        'total_days' => 'integer',
        'status'     => LeaveRequestStatus::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'current_approver_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class);
    }
}
