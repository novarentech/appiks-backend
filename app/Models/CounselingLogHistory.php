<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounselingLogHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'counseling_log_histories';

    public function counselingLog()
    {
        return $this->belongsTo(CounselingLog::class, 'counseling_log_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'clinical_notes' => 'encrypted',
        ];
    }
}
