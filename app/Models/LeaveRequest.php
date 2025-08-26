<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'notes',
        'company_id',
        'employee_id',
        'leave_type_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at'   => 'date',
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

    public function getTotalDaysAttribute(): int
    {
        return $this->started_at->diffInDays($this->ended_at) + 1;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->started_at->isPast() && $this->ended_at->isFuture();
    }
}
