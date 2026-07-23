<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function admins()
    {
        return $this->hasMany(User::class)->where('role', UserRole::ADMIN->value);
    }

    public function headteacher()
    {
        return $this->hasOne(User::class)->where('role', UserRole::HEADTEACHER->value);
    }

    public function counselors()
    {
        return $this->hasMany(User::class)->where('role', UserRole::COUNSELOR->value);
    }

    public function teachers()
    {
        return $this->hasMany(User::class)->where('role', UserRole::TEACHER->value);
    }

    public function students()
    {
        return $this->hasMany(User::class)->where('role', UserRole::STUDENT->value);
    }

    public function psycologist()
    {
        return $this->hasMany(User::class)->where('role', UserRole::PSYCHOLOGIST->value);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
