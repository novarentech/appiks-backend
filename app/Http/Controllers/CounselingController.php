<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\CreateCounselingRequest;
use App\Http\Resources\CounselingResource;
use App\Models\Counseling;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CounselingController extends Controller
{
    use ApiResponder;

    /**
     * Create new counseling
     *
     * Membuat sebuah sesi konseling baru baik internal (guru) maupun external (psikologi)
     */
    #[Group('Counseling')]
    public function store(CreateCounselingRequest $request){
        $payload = $request->except('date','time');
        // return $this->success($payload);
        $counseling = Counseling::create($payload);
        return $this->success(new CounselingResource($counseling));
    }

    /**
     * Get counseling detail
     *
     * Melihat detail sebuah sesi konseling
     */
    #[Group('Counseling')]
    public function show(Counseling $counseling){
        $counseling->load(['student','counselor']);
        return $this->success(new CounselingResource($counseling));
    }
}
