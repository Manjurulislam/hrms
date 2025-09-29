<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceSession extends Model
{
    use SoftDeletes;

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
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_lat' => 'decimal:8',
        'check_in_long' => 'decimal:8',
        'check_out_lat' => 'decimal:8',
        'check_out_long' => 'decimal:8',
        'is_late' => 'boolean',
        'is_early_departure' => 'boolean',
        'is_overtime' => 'boolean',
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
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
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
        return $this->status === 'active';
    }

    // Methods
    public function calculateDuration(): void
    {
        if ($this->check_in_time && $this->check_out_time) {
            $this->duration_minutes = $this->check_in_time->diffInMinutes($this->check_out_time);
            $this->save();
        }
    }

    public function checkOut($ip, $location = 'office', $lat = null, $long = null, $note = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'check_out_time' => now(),
            'check_out_ip' => $ip,
            'check_out_location' => $location,
            'check_out_lat' => $lat,
            'check_out_long' => $long,
            'check_out_note' => $note,
            'status' => 'completed',
        ]);

        $this->calculateDuration();

        return true;
    }

    public function autoClose(): void
    {
        if ($this->status === 'active') {
            // Auto close at end of day (configurable)
            $endOfDay = $this->check_in_time->copy()->endOfDay();

            $this->update([
                'check_out_time' => $endOfDay,
                'check_out_note' => 'Auto closed by system',
                'status' => 'auto_closed',
            ]);

            $this->calculateDuration();
        }
    }
}