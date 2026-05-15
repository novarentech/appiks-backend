<?php

namespace App\Actions;

use App\Enums\MoodStatus;
use Illuminate\Support\Collection;

class BuildMoodRecapAction
{
    /**
     * Hitung recap, mean, secure count, insecure count dari koleksi mood.
     *
     * @param Collection $moods
     * @return array{recap: array, mean: string, secure: int, insecure: int}
     */
    public function handle(Collection $moods): array
    {
        $recap = $moods->groupBy('status')->map(fn($items) => $items->count());

        $secure   = ($recap[MoodStatus::NEUTRAL->value] ?? 0) + ($recap[MoodStatus::HAPPY->value] ?? 0);
        $insecure = ($recap[MoodStatus::ANGRY->value] ?? 0)  + ($recap[MoodStatus::SAD->value] ?? 0);
        $mean     = $secure > $insecure ? 'secure' : 'insecure';

        return compact('recap', 'mean', 'secure', 'insecure');
    }
}
