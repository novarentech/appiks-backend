<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;
    protected $hidden = ['pivot'];

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'video_tag');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }
}
