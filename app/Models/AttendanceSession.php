<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Traits\CompanySettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceSession extends Model
{
    use SoftDeletes;
    use CompanySettings;

    protected $fillable = [
        'employee_id',
        'company_id',
        'department_id',
        'attendance_date',
        'session_number',
        'check_in_time',
        'scheduled_start_time',
        'check_in_ip',
        'check_in_location',
        'check_in_lat',
        'check_in_long',
        'check_in_note',
        'check_out_time',
        'scheduled_end_time',
        'check_out_ip',
        'check_out_location',
        'check_out_lat',
        'check_out_long',
        'check_out_note',
        'duration_minutes',
        'session_type',
        'status',
        'is_late',
        'is_early_departure',
        'is_overtime',
    ];

    protected $casts = [
        'attendance_date'    => 'date',
        'check_in_time'      => 'datetime',
        'check_out_time'     => 'datetime',
        'check_in_lat'       => 'decimal:8',
        'check_in_long'      => 'decimal:8',
        'check_out_lat'      => 'decimal:8',
        'check_out_long'     => 'decimal:8',
        'is_late'            => 'boolean',
        'is_early_departure' => 'boolean',
        'is_overtime'        => 'boolean',
        'session_type'       => SessionType::class,
        'status'             => SessionStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function summary(): BelongsTo
    {
        return $this->belongsTo(AttendanceSummary::class, 'employee_id', 'employee_id')
            ->where('attendance_date', $this->attendance_date);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', SessionStatus::Active);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', SessionStatus::Completed);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', today());
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Accessors
    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->duration_minutes) {
            return null;
        }
        return round($this->duration_minutes / 60, 2);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === SessionStatus::Active;
    }

    // Methods

    public function checkOut($ip, $location = 'office', $lat = null, $long = null, $note = null): bool
    {
        if ($this->status !== SessionStatus::Active) {
            return false;
        }

        $this->update([
            'check_out_time'     => now(),
            'check_out_ip'       => $ip,
            'check_out_location' => $location,
            'check_out_lat'      => $lat,
            'check_out_long'     => $long,
            'check_out_note'     => $note,
            'status'             => SessionStatus::Completed,
        ]);

        $this->calculateDuration();

        return true;
    }

    public function calculateDuration(): void
    {
        if ($this->check_in_time && $this->check_out_time) {
            $this->duration_minutes = (int)abs($this->check_in_time->diffInMinutes($this->check_out_time));
            $this->save();
        }
    }

    public function autoClose(): void
    {
        if ($this->status !== SessionStatus::Active) {
            return;
        }

        // Use office end time instead of end of day so extra time is not counted
        $company   = $this->company;
        $officeEnd = Carbon::parse($this->companySetting($company, 'office_end'))
            ->setDate($this->attendance_date->year, $this->attendance_date->month, $this->attendance_date->day);

        // If check-in was after office end (shouldn't happen with new validation), use check-in time
        $checkOutTime = $this->check_in_time->gt($officeEnd) ? $this->check_in_time : $officeEnd;

        $this->update([
            'check_out_time' => $checkOutTime,
            'check_out_note' => 'Auto closed by system',
            'status'         => SessionStatus::AutoClosed,
        ]);
        
        $this->calculateDuration();
    }
}
