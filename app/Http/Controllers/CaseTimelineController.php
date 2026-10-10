<?php

namespace App\Http\Controllers;

use App\Enums\CaseEventType;
use App\Http\Resources\CaseEventResource;
use App\Models\CaseEvent;
use App\Models\Counseling;
use App\Models\Sharing;
use App\Support\CaseTimelineVisibility;
use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CaseTimelineController extends Controller
{
    use ApiResponder;

    /**
     * Get case timeline by sharing
     *
     * Jejak penanganan sebuah kasus, dari curhat dikirim sampai kasus ditutup.
     * Curhat adalah entry point: satu curhat bisa menghasilkan lebih dari satu
     * sesi konseling, dan seluruhnya ikut terangkum di sini.
     *
     * Akses saat ini terbatas pada Kepala Sekolah.
     */
    #[Group('Case Timeline')]
    public function bySharing(Request $request, Sharing $sharing): JsonResponse
    {
        Gate::authorize('viewTimeline', $sharing);

        $events = $this->visibleEvents(
            CaseEvent::where('sharing_id', $sharing->id),
            $request
        );

        return $this->success([
            'sharing_id' => $sharing->id,
            'opened_at'  => $sharing->created_at?->toIso8601String(),
            'events'     => CaseEventResource::collection($events),
        ], 'Jejak penanganan kasus berhasil diambil.');
    }

    /**
     * Get case timeline by counseling
     *
     * Jejak yang sama, tapi masuk dari sisi sesi konseling — dipakai saat
     * sebuah sesi tidak berasal dari curhat (dijadwalkan dari laporan).
     *
     * Akses saat ini terbatas pada Kepala Sekolah.
     */
    #[Group('Case Timeline')]
    public function byCounseling(Request $request, Counseling $counseling): JsonResponse
    {
        Gate::authorize('viewTimeline', $counseling);

        // Bila sesi ini berasal dari curhat, tampilkan jejak utuh sejak curhat
        // dikirim — bukan hanya potongan sejak sesinya dibuat.
        $query = $counseling->sharing_id === null
            ? CaseEvent::where('counseling_id', $counseling->id)
            : CaseEvent::where('sharing_id', $counseling->sharing_id);

        $events = $this->visibleEvents($query, $request);

        return $this->success([
            'counseling_id' => $counseling->id,
            'sharing_id'    => $counseling->sharing_id,
            'events'        => CaseEventResource::collection($events),
        ], 'Jejak penanganan kasus berhasil diambil.');
    }

    /**
     * Saring jenis kejadian yang boleh dilihat peran pemanggil, lalu urutkan
     * secara kronologis.
     */
    private function visibleEvents($query, Request $request)
    {
        $role = $request->user()?->role;

        $allowed = array_values(array_filter(
            CaseEventType::cases(),
            fn (CaseEventType $event) => CaseTimelineVisibility::canSeeEvent($role, $event)
        ));

        return $query
            ->whereIn('event', array_column($allowed, 'value'))
            ->with('actor:id,name')
            ->chronological()
            ->get();
    }
}
