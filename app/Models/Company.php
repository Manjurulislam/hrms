<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'website',
        'office_start',
        'office_end',
        'office_ip',
        'work_hours',
        'half_day_hours',
        'late_grace',
        'early_grace',
        'max_sessions',
        'min_session_gap',
        'max_breaks',
        'auto_close',
        'auto_close_at',
        'track_ip',
        'track_location',
        'status',
    ];

    protected $casts = [
        'work_hours'      => 'integer',
        'half_day_hours'  => 'integer',
        'late_grace'      => 'integer',
        'early_grace'     => 'integer',
        'max_sessions'    => 'integer',
        'min_session_gap' => 'integer',
        'max_breaks'      => 'integer',
        'auto_close'      => 'boolean',
        'track_ip'        => 'boolean',
        'track_location'  => 'boolean',
        'status'          => 'boolean',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function leaveTypes(): HasMany
    {
        return $this->hasMany(LeaveType::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(CompanyWorkingDay::class);
    }

    public function managedEmployees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'company_employee')
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }
}
