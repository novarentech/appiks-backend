<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelfHelp extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'self_helps';

    protected $guarded = [];

    protected function casts()
    {
        return [
            'content' => 'json',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
