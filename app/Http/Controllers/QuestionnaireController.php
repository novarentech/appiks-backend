<?php

namespace App\Http\Controllers;

use App\Actions\AnalyzeInsecureQuestionnaireAction;
use App\Actions\AnalyzeSecureQuestionnaireAction;
use App\Actions\ConvertAnswersToAlphabetAction;
use App\Enums\MoodStatus;
use App\Enums\UserRole;
use App\Http\Requests\AnalyzeQuestionnaireRequest;
use App\Http\Resources\QuestionnaireResource;
use App\Models\Questionnaire;
use App\Models\User;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Dedoc\Scramble\Attributes\ExcludeAllRoutesFromDocs;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

#[ExcludeAllRoutesFromDocs]
class QuestionnaireController extends Controller
{
    use ApiResponder;

    #[Group('Questionnaire')]
    public function getAllQuestionnaires()
    {
        Gate::allowIf(fn(User $user) => $user->role == UserRole::STUDENT->value);
        $type = MoodStatus::from(Auth::user()->last_mood)->isSecure() ? 'secure' : 'insecure';

        return $this->success(QuestionnaireResource::collection(
            Questionnaire::where('type', $type)->get()
        ));
    }

    #[Group('Questionnaire')]
    public function getOneQuestionnaire(string $type, int $order)
    {
        return $this->success(new QuestionnaireResource(
            Questionnaire::whereType($type)->whereOrder($order)->first()
        ));
    }

    #[Group('Questionnaire')]
    public function analyzeQuestionnaire(
        AnalyzeQuestionnaireRequest $request,
        string $type,
        ConvertAnswersToAlphabetAction $converter,
        AnalyzeSecureQuestionnaireAction $secureAction,
        AnalyzeInsecureQuestionnaireAction $insecureAction,
    ) {
        $answers = $converter->handle($type, $request->validated()['answers']);

        $result = $type === 'insecure'
            ? $insecureAction->handle($answers)
            : $secureAction->handle($answers);

        return $this->success($result);
    }
}
