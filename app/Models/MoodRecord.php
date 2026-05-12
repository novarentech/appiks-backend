<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoodRecord extends Model
{
    /** @use HasFactory<\Database\Factories\MoodRecordFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
