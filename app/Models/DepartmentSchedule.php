<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentSchedule extends Model
{
    protected $fillable = [
        'department_id',
        'work_days',
        'work_start_time',
        'work_end_time',
    ];

    protected $casts = [
        'work_days'       => 'array',
        'work_start_time' => 'datetime',
        'work_end_time'   => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Accessor to get formatted work start time (only time part)
    public function getWorkStartTimeFormattedAttribute(): string
    {
        return $this->work_start_time ? $this->work_start_time->format('H:i') : '';
    }

    // Accessor to get formatted work end time (only time part)
    public function getWorkEndTimeFormattedAttribute(): string
    {
        return $this->work_end_time ? $this->work_end_time->format('H:i') : '';
    }

    // Accessor to get work days as comma-separated string
    public function getWorkDaysStringAttribute(): string
    {
        if (!$this->work_days) {
            return '';
        }

        $dayNames = [
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
            'sunday'    => 'Sunday',
        ];

        return collect($this->work_days)
            ->map(fn($day) => $dayNames[$day] ?? $day)
            ->implode(', ');
    }

    // Check if a specific day is a work day
    public function isWorkDay(string $day): bool
    {
        return in_array(strtolower($day), $this->work_days ?? []);
    }

    // Get total work hours per day
    public function getDailyWorkHoursAttribute(): float
    {
        if (!$this->work_start_time || !$this->work_end_time) {
            return 0;
        }

        $start = $this->work_start_time;
        $end   = $this->work_end_time;

        // Handle case where end time is next day
        if ($end < $start) {
            $end = $end->addDay();
        }

        return $start->diffInHours($end);
    }

    // Get total work hours per week
    public function getWeeklyWorkHoursAttribute(): float
    {
        return $this->daily_work_hours * count($this->work_days ?? []);
    }
}
