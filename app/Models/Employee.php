<?php

namespace App\Models;

use App\Enums\BloodGroup;
use App\Enums\EmpStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Services\Utility\MenuService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected static function booted(): void
    {
        // Gaining/losing a subordinate flips the "manager" menu flag for the
        // affected manager(s), so drop their cached menu when manager_id changes.
        static::saved(function (Employee $employee) {
            if (!$employee->wasChanged('manager_id') && !$employee->wasRecentlyCreated) {
                return;
            }

            collect([$employee->getOriginal('manager_id'), $employee->manager_id])
                ->filter()
                ->unique()
                ->each(function ($managerId) {
                    if ($userId = User::where('employee_id', $managerId)->value('id')) {
                        MenuService::forget($userId);
                    }
                });
        });
    }

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
        'company_id',
        'department_id',
        'designation_id',
        'manager_id',
        'emp_status',
        'status',
        'date_of_birth',
        'joining_date',
    ];

    protected $casts = [
        'status'         => 'boolean',
        'gender'         => Gender::class,
        'emp_status'     => EmpStatus::class,
        'blood_group'    => BloodGroup::class,
        'marital_status' => MaritalStatus::class,
        'date_of_birth'  => 'date',
        'joining_date'   => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceSummaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }

    public function managedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_employee')
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar') ?: null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }
}
