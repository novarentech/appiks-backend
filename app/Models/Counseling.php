<?php

namespace App\Models;

use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use Illuminate\Database\Eloquent\Model;

class Counseling extends Model
{
    protected $guarded = [];
    protected $hidden = ['deleted_at'];
    protected $table = 'counselings';

    public function student(){
        return $this->belongsTo(User::class,'student_id');
    }
    
    public function counselor(){
        return $this->belongsTo(User::class,'counselor_id');
    }

    protected function casts(){
        return [
            'resolution' => CounselingResolution::class,
            'status' => CounselingStatus::class,
            'scheduled_at' => 'datetime',
            'cutdown_at' => 'datetime',
        ];
    }
}
