<?php

namespace App\Actions;

use App\Models\Questionnaire;

class ConvertAnswersToAlphabetAction
{
    public function handle(string $type, array $answers): array
    {
        $questionnaires = Questionnaire::where('type', $type)->orderBy('order')->get();
        $alphabetAnswers = [];

        foreach ($answers as $key => $item) {
            foreach ($questionnaires[$key]->answers as $alpha => $text) {
                if (strtolower($text['text']) == strtolower($item)) {
                    $alphabetAnswers[] = $alpha;
                }
            }
        }

        return $alphabetAnswers;
    }
}
