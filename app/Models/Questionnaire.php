<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Questionnaire extends Model
{
    use SoftDeletes;
    protected $hidden = ['id'];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'order' => 'integer',
        ];
    }
}
