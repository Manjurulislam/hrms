<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'note',
        'request_ip',
        'lat',
        'long',
        'status',
        'checkin',
        'checkout',
        'attend_at',
    ];

    protected $casts = [
        'checkin'   => 'datetime',
        'checkout'  => 'datetime',
        'attend_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getTotalHoursAttribute(): ?float
    {
        if (!$this->checkin || !$this->checkout) {
            return null;
        }
        return $this->checkin->diffInHours($this->checkout, true);
    }

    public function getTotalMinutesAttribute(): ?int
    {
        if (!$this->checkin || !$this->checkout) {
            return null;
        }
        return $this->checkin->diffInMinutes($this->checkout, true);
    }
}
