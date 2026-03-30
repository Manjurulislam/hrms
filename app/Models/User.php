<?php

namespace App\Models;


use App\Mail\ResetPasswordMail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function hasRole($slug): bool
    {
        return $this->roles()->where('slug', $slug)->exists();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = url(route('password.reset', ['token' => $token, 'email' => $this->email], false));

        Mail::to($this->email)->send(new ResetPasswordMail($url, $this->name));
    }

    public function isEmployee(): bool
    {
        return !blank($this->employee_id);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'boolean',
        ];
    }
}
