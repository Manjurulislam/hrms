<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the configuration options for the attendance
    | tracking system including session limits, break rules, and timing constraints.
    |
    */

    // Session Configuration
    'minimum_session_duration' => env('ATTENDANCE_MIN_SESSION_DURATION', 1), // minutes
    'minimum_session_gap' => env('ATTENDANCE_MIN_SESSION_GAP', 2), // minutes between sessions
    'max_sessions_per_day' => env('ATTENDANCE_MAX_SESSIONS_PER_DAY', 10),

    // Break Configuration
    'max_breaks_per_day' => env('ATTENDANCE_MAX_BREAKS_PER_DAY', 5),
    'minimum_break_duration' => env('ATTENDANCE_MIN_BREAK_DURATION', 1), // minutes
    'maximum_break_duration' => env('ATTENDANCE_MAX_BREAK_DURATION', 120), // 2 hours

    // Office Hours (default if not set in department)
    'default_office_start' => env('ATTENDANCE_DEFAULT_START', '09:00'),
    'default_office_end' => env('ATTENDANCE_DEFAULT_END', '18:00'),

    // Grace Period
    'late_grace_period' => env('ATTENDANCE_LATE_GRACE_PERIOD', 15), // minutes
    'early_leave_grace_period' => env('ATTENDANCE_EARLY_LEAVE_GRACE_PERIOD', 15), // minutes

    // Auto Close Configuration
    'auto_close_enabled' => env('ATTENDANCE_AUTO_CLOSE_ENABLED', true),
    'auto_close_time' => env('ATTENDANCE_AUTO_CLOSE_TIME', '23:59'), // Time to auto-close active sessions

    // Working Hours
    'standard_working_hours' => env('ATTENDANCE_STANDARD_HOURS', 8), // hours per day
    'half_day_hours' => env('ATTENDANCE_HALF_DAY_HOURS', 4), // minimum hours for half day

    // IP Tracking
    'track_ip_address' => env('ATTENDANCE_TRACK_IP', true),
    'track_location' => env('ATTENDANCE_TRACK_LOCATION', true),

    // Notification Settings
    'send_late_notifications' => env('ATTENDANCE_SEND_LATE_NOTIFICATIONS', true),
    'send_absence_notifications' => env('ATTENDANCE_SEND_ABSENCE_NOTIFICATIONS', true),
];