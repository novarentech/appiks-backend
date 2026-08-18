<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalSummary extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clinical_summaries';

    protected $fillable = [
        'counseling_id',
        'summary_data',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
        ];
    }

    public function counseling()
    {
        return $this->belongsTo(Counseling::class);
    }
}
