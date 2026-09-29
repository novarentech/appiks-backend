<?php

namespace App\Http\Controllers;

use App\Actions\Psychologist\CreatePsychologistSlotAction;
use App\Actions\Psychologist\DeletePsychologistSlotAction;
use App\Actions\Psychologist\GetPsychologistSlotsAction;
use App\Http\Requests\StorePsychologistSlotRequest;
use App\Http\Resources\PsychologistSlotResource;
use App\Models\PsychologistSlot;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PsychologistSlotController extends Controller
{
    use ApiResponder;

    /**
     * Get psychologist slots
     *
     * Retrieve a list of consultation time slots for the authenticated partner psychologist, with optional date range filter.
     *
     * @queryParam start string Filter tanggal awal slot (YYYY-MM-DD). Default: today. Example: 2026-08-01
     * @queryParam end string Filter tanggal akhir slot (YYYY-MM-DD). Example: 2026-08-31
     */
    #[Group('Psychologist')]
    public function index(Request $request, GetPsychologistSlotsAction $action): JsonResponse
    {
        Gate::authorize('manage', PsychologistSlot::class);

        $slots = $action->handle(
            auth()->user()->psychologistProfile,
            $request->query('start'),
            $request->query('end')
        );

        return $this->success(PsychologistSlotResource::collection($slots), 'Daftar slot berhasil diambil.');
    }

    /**
     * Create psychologist slot
     *
     * Publish a new consultation time slot. Prevents past dates, invalid start/end times, and overlapping schedules.
     *
     * @bodyParam repeat boolean Opsional. Jika bernilai true, slot akan berulang setiap minggu pada hari dan jam yang sama selama 1 tahun ke depan. Example: false
     */
    #[Group('Psychologist')]
    public function store(StorePsychologistSlotRequest $request, CreatePsychologistSlotAction $action): JsonResponse
    {
        $slots = $action->handle($request->validated(), auth()->user()->psychologistProfile);

        return $this->created(PsychologistSlotResource::collection($slots), 'Slot berhasil ditambahkan.');
    }

    /**
     * Delete psychologist slot
     *
     * Safely soft-delete an existing consultation time slot after today without an active booking schedule.
     * Supports optional repeat flag to delete recurring weekly slots.
     *
     * @queryParam repeat boolean Opsional. Jika true, akan menghapus slot ini dan seluruh rangkaian slot berulang mingguannya yang belum dibooking hingga 1 tahun ke depan. Example: false
     */
    #[Group('Psychologist')]
    public function destroy(Request $request, PsychologistSlot $slot, DeletePsychologistSlotAction $action): JsonResponse
    {
        Gate::authorize('delete', $slot);

        $action->handle($slot, $request->boolean('repeat'));

        return $this->success(null, 'Slot berhasil dihapus.');
    }
}
