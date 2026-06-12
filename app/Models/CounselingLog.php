<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounselingLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'counseling_logs';

    public function counseling()
    {
        return $this->belongsTo(Counseling::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function histories()
    {
        return $this->hasMany(CounselingLogHistory::class, 'counseling_log_id');
    }

    protected function casts(): array
    {
        return [
            'clinical_notes' => 'encrypted',
        ];
    }
}
