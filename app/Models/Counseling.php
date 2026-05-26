<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Counseling extends Pivot
{
    protected $guarded = [];

    public function student(){
        return $this->belongsTo(User::class,'student_id');
    }
    
    public function counselor(){
        return $this->belongsTo(User::class,'counselor_id');
    }
}
