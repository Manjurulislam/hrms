<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_session_id',
        'attendance_date',
        'break_start',
        'break_end',
        'duration_minutes',
        'break_type',
        'reason',
        'is_paid',
        'max_allowed_minutes',
        'break_start_ip',
        'break_end_ip',
        'status',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'is_paid' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
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

    // Accessors
    public function getDurationHoursAttribute(): ?float
    {
        if (!$this->duration_minutes) {
            return null;
        }
        return round($this->duration_minutes / 60, 2);
    }

    // Methods
    public function endBreak($ip): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $breakEnd = now();
        $duration = $this->break_start->diffInMinutes($breakEnd);

        $this->update([
            'break_end' => $breakEnd,
            'break_end_ip' => $ip,
            'duration_minutes' => $duration,
            'status' => 'completed',
        ]);

        return true;
    }
}