<?php

namespace App\Models;

use App\Enums\ConsentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounselingConsent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'counseling_consents';

    protected $fillable = [
        'counseling_id',
        'status',
        'scopes',
        'granted_at',
        'rejected_at',
    ];

    public function counseling()
    {
        return $this->belongsTo(Counseling::class);
    }

    protected function casts(): array
    {
        return [
            'status' => ConsentStatus::class,
            'scopes' => 'array',
            'granted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
