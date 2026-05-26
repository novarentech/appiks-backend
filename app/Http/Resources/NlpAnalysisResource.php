<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $text
 * @property array|null $response
 * @property mixed $flag
 * @property mixed $status
 * @property mixed $created_at
 */
class NlpAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,

            'response' => [
                'total_score' => data_get($this->response, 'total_score'),

                'zone_status' => data_get(
                    $this->response,
                    'zone_status'
                ),
                'matched_keywords' => collect(
                    data_get($this->response, 'matched_keywords', [])
                )
                    ->map(
                        fn (array $keyword): array => [
                            'stem' => $keyword['stem'] ?? null,
                            'zone' => $keyword['zone'] ?? null,
                            'weight' => $keyword['weight'] ?? null,
                        ]
                    )
                    ->values()
                    ->all(),
            ],

            'reason' => $this->reason,
            'flag' => $this->flag,
            'status' => $this->status?->value,
            'created_at' => $this->created_at,
        ];
    }
}