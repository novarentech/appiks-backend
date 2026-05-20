<?php

namespace App\Models;

use App\Enums\NlpAnalysisStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $text
 * @property array{total_score: int, zone_status: string, matched_keywords: array<int, array{stem: string, zone: string, weight: int}>}|null $response
 * @property string|null $flag
 * @property NlpAnalysisStatus|null $status
 * @property string|null $nlpable_type
 * @property int|null $nlpable_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class NlpAnalysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    /**
     * Get the parent nlpable model (e.g. Sharing).
     */
    public function nlpable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response' => 'json',
            'status' => NlpAnalysisStatus::class,
        ];
    }
}
