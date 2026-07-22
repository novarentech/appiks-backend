<?php

namespace App\Models;

use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Counseling extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $hidden = ['deleted_at'];
    protected $table = 'counselings';

    public function student(){
        return $this->belongsTo(User::class,'student_id');
    }
    
    public function counselor(){
        return $this->belongsTo(User::class,'counselor_id');
    }

    public function report(){
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function logs(){
        return $this->hasMany(CounselingLog::class, 'counseling_id');
    }

    public function consents(){
        return $this->hasMany(CounselingConsent::class, 'counseling_id');
    }

    public function latestConsent(){
        return $this->hasOne(CounselingConsent::class, 'counseling_id')->latestOfMany();
    }

    public function clinicalSummary(){
        return $this->hasOne(ClinicalSummary::class, 'counseling_id');
    }

    public function psychologist(){
        return $this->belongsTo(User::class, 'psychologist_id');
    }

    public function sharing(){
        return $this->belongsTo(Sharing::class, 'sharing_id');
    }

    public function bookingSchedule()
    {
        return $this->hasOne(BookingSchedule::class, 'counseling_id');
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
