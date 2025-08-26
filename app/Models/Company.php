<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
