<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentSchedule extends Model
{

    protected $fillable = [
        'department_id',
        'company_id',
        'office_ip',
        'work_start_time',
        'work_end_time',
        'status',
    ];


    protected $casts = [
        'office_ip' => 'string',
        'status'    => 'boolean',
    ];


    /**
     * Get the department that owns the schedule.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
}
