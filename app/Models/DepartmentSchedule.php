<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentSchedule extends Model
{

    protected $fillable = [
        'department_id',
        'work_start_time',
        'work_end_time',
    ];


    /**
     * Get the department that owns the schedule.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the working days for this schedule.
     */
    public function workingDays(): HasMany
    {
        return $this->hasMany(DepartmentWorkingDay::class);
    }

    /**
     * Get active working days for this schedule.
     */
    public function activeWorkingDays(): HasMany
    {
        return $this->hasMany(DepartmentWorkingDay::class)->where('status', true);
    }

    /**
     * Scope a query to only include schedules for a specific department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
