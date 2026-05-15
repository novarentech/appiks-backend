<?php

namespace App\Actions;

class AnalyzeInsecureQuestionnaireAction
{
    public function __construct(
        private AnalyzeInsecurePersonaAction $personaAction,
        private BuildLearningModeAction $learningAction,
        private GenerateMissionAction $missionAction,
    ) {}

    public function handle(array $answers): array
    {
        $first  = $this->personaAction->handle(array_slice($answers, 0, 5));
        $second = $this->learningAction->handle(array_slice($answers, 5, 3));
        $third  = $this->missionAction->handleFuel(array_slice($answers, 8, 2));

        $archetype = [
            'type'        => ['main' => $first->main_archtype, 'secondary' => $first->secondary_archtype],
            'character'   => $first->archtype_character,
            'habits'      => $first->archtype_habits,
            'description' => $first->archtype_description,
            'power'       => $first->archtype_power,
        ];

        $mission = $this->missionAction->handleMission([$archetype, $second, $third]);

        return ['archtype' => $archetype, 'learn' => $second, 'fuel' => $third, 'mission' => $mission];
    }
}
