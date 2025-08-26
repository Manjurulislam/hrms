<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Employee extends Model
{
    protected $fillable = [
        'id_no',
        'first_name',
        'last_name',
        'email',
        'phone',
        'sec_phone',
        'nid',
        'gender',
        'qualification',
        'emergency_contact',
        'blood_group',
        'marital_status',
        'bank_account',
        'address',
        'department_id',
        'status',
        'date_of_birth',
        'joining_date',
        'probation_end_at',
    ];

    protected $casts = [
        'status'           => 'boolean',
        'date_of_birth'    => 'date',
        'joining_date'     => 'date',
        'probation_end_at' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designations(): BelongsToMany
    {
        return $this->belongsToMany(Designation::class, 'designation_employee')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function company(): HasOneThrough
    {
        return $this->hasOneThrough(Company::class, Department::class, 'id', 'id', 'department_id', 'company_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
