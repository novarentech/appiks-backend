<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\PsychologistProfile;
use App\Http\Requests\StorePsychologistRequest;
use App\Http\Requests\UpdatePsychologistRequest;
use App\Http\Resources\UserResource;
use App\Actions\Psychologist\StorePsychologistAction;
use App\Actions\Psychologist\UpdatePsychologistAction;
use App\Actions\Psychologist\DeletePsychologistAction;
use App\Actions\Psychologist\TogglePsychologistStatusAction;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PsychologistController extends Controller
{
    use ApiResponder;

    /**
     * Get all partner psychologists.
     *
     * List registered Partner Psychologists with dynamic search and optional pagination.
     */
    #[Group('Psychologist')]
    public function index(Request $request)
    {
        Gate::authorize('viewAny', PsychologistProfile::class);

        $query = User::where('role', UserRole::PSYCHOLOGIST->value)
            ->with('psychologistProfile');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('identifier', 'like', "%{$search}%")
                  ->orWhereHas('psychologistProfile', function ($profileQ) use ($search) {
                      $profileQ->where('institution_name', 'like', "%{$search}%")
                               ->orWhere('str_number', 'like', "%{$search}%")
                               ->orWhere('specialization', 'like', "%{$search}%");
                  });
            });
        }

        // Apply pagination if page or limit query parameters exist, otherwise return flat array
        if ($request->has('page') || $request->has('limit')) {
            $limit = $request->get('limit', 10);
            $psychologists = $query->paginate($limit);
            return $this->success(UserResource::collection($psychologists));
        }

        $psychologists = $query->get();
        return $this->success(UserResource::collection($psychologists));
    }

    /**
     * Create a new partner psychologist.
     *
     * Register a new Partner Psychologist and create their profile.
     */
    #[Group('Psychologist')]
    public function store(StorePsychologistRequest $request, StorePsychologistAction $action)
    {
        Gate::authorize('create', PsychologistProfile::class);

        $psychologist = $action->handle($request->validated());

        return $this->success(new UserResource($psychologist), 'Success creating partner psychologist account');
    }

    /**
     * Update partner psychologist details.
     *
     * Update details of an existing Partner Psychologist.
     */
    #[Group('Psychologist')]
    public function update(UpdatePsychologistRequest $request, User $psychologist, UpdatePsychologistAction $action)
    {
        Gate::authorize('update', $psychologist->psychologistProfile ?? PsychologistProfile::class);

        $updated = $action->handle($psychologist, $request->validated());

        return $this->success(new UserResource($updated), 'Success updating partner psychologist profile');
    }

    /**
     * Toggle active status.
     *
     * Toggle the active status of a Partner Psychologist.
     */
    #[Group('Psychologist')]
    public function toggleStatus(User $psychologist, TogglePsychologistStatusAction $action)
    {
        Gate::authorize('update', $psychologist->psychologistProfile ?? PsychologistProfile::class);

        $updated = $action->handle($psychologist);

        return $this->success(new UserResource($updated), 'Success toggling partner psychologist active status');
    }

    /**
     * Delete a partner psychologist.
     *
     * Soft delete a Partner Psychologist and their associated profile.
     */
    #[Group('Psychologist')]
    public function destroy(User $psychologist, DeletePsychologistAction $action)
    {
        Gate::authorize('delete', $psychologist->psychologistProfile ?? PsychologistProfile::class);

        $action->handle($psychologist);

        return $this->success(null, 'Success deleting partner psychologist account');
    }
}
