<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentWorkingDay extends Model
{
    protected $fillable = [
        'day',
        'department_schedule_id',
        'status',
    ];


    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the department schedule that owns this working day.
     */
    public function departmentSchedule(): BelongsTo
    {
        return $this->belongsTo(DepartmentSchedule::class);
    }

    /**
     * Get the department through the schedule relationship.
     */
    public function department()
    {
        return $this->departmentSchedule->department();
    }

    /**
     * Scope a query to only include active working days.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include inactive working days.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope a query to filter by specific day.
     */
    public function scopeForDay($query, $day)
    {
        return $query->where('day', $day);
    }

    /**
     * Scope a query to filter by department schedule.
     */
    public function scopeForSchedule($query, $scheduleId)
    {
        return $query->where('department_schedule_id', $scheduleId);
    }
}
