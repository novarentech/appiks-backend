<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = ['views' => 'integer'];

    protected $hidden = ['updated_at', 'school_id'];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'video_tag');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
