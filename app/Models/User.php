<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'verified' => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function mood()
    {
        return $this->hasMany(MoodRecord::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function counselored()
    {
        return $this->hasMany(User::class, 'counselor_id', 'id')->where('role', UserRole::STUDENT->value);
    }

    public function mentored()
    {
        return $this->hasMany(User::class, 'mentor_id', 'id')->where('role', UserRole::STUDENT->value);
    }

    public function sharing()
    {
        return $this->hasMany(Sharing::class);
    }

    public function report()
    {
        return $this->hasMany(Report::class);
    }

    public function getLastMoodAttribute(): ?string
    {
        return $this->mood()->whereRecorded(now()->toDateString())->first()?->status;
    }

    public function lastmoodres()
    {
        return $this->hasOne(MoodRecord::class)->whereRecorded(now()->toDateString());
    }

    public function cloud()
    {
        return $this->hasOne(Cloud::class);
    }
}
