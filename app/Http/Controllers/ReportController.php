<?php

namespace App\Http\Controllers;

use App\Actions\ScheduleReportCounselingAction;
use App\Enums\CounselingStatus;
use App\Enums\MoodStatus;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Events\ReportCreated;
use App\Http\Requests\CloseReportRequest;
use App\Http\Requests\ConfirmReportRequest;
use App\Http\Requests\CreateReportRequest;
use App\Http\Requests\GetReportRequest;
use App\Http\Requests\RescheduleReportRequest;
use App\Http\Resources\CounselingResource;
use App\Http\Resources\ReportResource;
use App\Models\Counseling;
use App\Models\Sharing;
use App\Models\User;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    use ApiResponder;

    /**
     * Get all report data
     *
     * Get latest reports data belongs to student for student or belongs to all student of counselored for counselor with filtering and searching.
     */
    #[Group('Report')]
    public function index(GetReportRequest $request): JsonResponse
    {
        $user = Auth::user();
        if ($user->role == UserRole::STUDENT->value) {
            $query = Counseling::where('student_id', $user->id)
                ->where('source_type', 'student')
                ->with(['counselor', 'student.room'])
                ->orderBy('scheduled_at');
        } elseif ($user->role == UserRole::COUNSELOR->value) {
            $query = Counseling::where('source_type', 'student')
                ->with(['counselor', 'student.room'])
                ->whereIn('student_id', $user->counselored->pluck('id'))
                ->latest('scheduled_at');
        } else {
            $query = Counseling::whereRaw('1 = 0');
        }

        $query->filterByRoom($request->input('room'))
            ->filterByStatus($request->input('status'))
            ->filterByPriority($request->input('priority'))
            ->search($request->input('search'));

        $reports = $query->get();

        return $this->success(ReportResource::collection($reports));
    }

    /**
     * Get report today count
     *
     * Mendapatkan data jumlah konseling berdasarkan tipenya
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function getReportCount()
    {
        Gate::authorize('viewGraph', Counseling::class);
        $user = Auth::user();
        $counselings = Counseling::where('source_type', 'student')
            ->whereDate('created_at', Carbon::today())
            ->whereIn('student_id', $user->counselored->pluck('id'))
            ->get();

        $statusMap = [
            'menunggu' => ReportStatus::MENUNGGU_TINJAUAN->value,
            'menunggu_jadwal' => ReportStatus::MENUNGGU_TINJAUAN->value,
            'menunggu_konfirmasi' => ReportStatus::MENUNGGU_TINJAUAN->value,
            'dijadwalkan' => ReportStatus::DIJADWALKAN->value,
            'dijadwal_ulang' => ReportStatus::DIJADWALKAN->value,
            'selesai' => ReportStatus::SELESAI->value,
            'ditolak' => ReportStatus::DITOLAK->value,
            'dibatalkan' => ReportStatus::DIBATALKAN->value,
            'menunggu_diskusi' => ReportStatus::MENUNGGU_DISKUSI->value,
        ];

        $countsByStatus = [];
        foreach ($counselings as $c) {
            $raw = $c->status instanceof CounselingStatus ? $c->status->value : $c->status;
            $mapped = $statusMap[$raw] ?? ReportStatus::MENUNGGU_TINJAUAN->value;
            $countsByStatus[$mapped] = ($countsByStatus[$mapped] ?? 0) + 1;
        }

        $statuses = ReportStatus::cases();
        $result = [];
        foreach ($statuses as $status) {
            $result[$status->value] = (int) ($countsByStatus[$status->value] ?? 0);
        }

        return $this->success($result);
    }

    /**
     * Create new report
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function store(CreateReportRequest $request)
    {
        $payload = [
            'student_id' => Auth::id(),
            'counselor_id' => Auth::user()->counselor_id,
            'room' => $request->room,
            'reason' => $request->topic,
            'scheduled_at' => $request->date . ' ' . $request->time,
            'type' => 'internal',
            'source_type' => 'student',
            'status' => CounselingStatus::MENUNGGU->value,
            'priority' => Auth::user()->last_mood == MoodStatus::ANGRY->value ? 'tinggi' : 'rendah',
            'notes' => $request->notes ?? null,
        ];

        $report = Counseling::create($payload);
        ReportCreated::dispatch($report);

        $report->load(['student', 'counselor']);

        return $this->created(new ReportResource($report));
    }

    /**
     * Get report detail
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function show(Counseling $report)
    {
        Gate::authorize('view', $report);
        $report->load(['student', 'counselor']);

        return $this->created(new ReportResource($report));
    }

    /**
     * Get all report of student
     *
     * Mendapatkan semua konseling seorang siswa. Hanya bisa diakses oleh Super Admin
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function reportOfStudent(User $user)
    {
        Gate::authorize('viewStudentReports', [Counseling::class, $user]);
        $reports = Counseling::where('source_type', 'student')
            ->with('counselor')
            ->where('student_id', $user->id)
            ->get();

        return $this->created(ReportResource::collection($reports));
    }

    /**
     * Confirm report meeting
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function confirm(ConfirmReportRequest $request, Counseling $report)
    {
        $report->update([
            'status' => CounselingStatus::DIJADWALKAN->value,
            'scheduled_at' => $request->date . ' ' . $request->time,
            'room' => $request->room,
            'notes' => $request->notes,
        ]);
        $report->load(['student', 'counselor']);

        return $this->created(new ReportResource($report));
    }

    /**
     * Reschedule report meeting
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function reschedule(RescheduleReportRequest $request, Counseling $report)
    {
        $report->update([
            'status' => CounselingStatus::DIJADWAL_ULANG->value,
            'scheduled_at' => $request->date . ' ' . $request->time,
            'room' => $request->room,
            'notes' => $request->notes,
        ]);
        $report->load(['student', 'counselor']);

        return $this->created(new ReportResource($report));
    }

    /**
     * Finish report meeting
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function close(CloseReportRequest $request, Counseling $report)
    {
        $report->update([
            'result' => $request->result,
            'status' => CounselingStatus::SELESAI->value,
        ]);
        $report->load(['student', 'counselor']);

        return $this->created(new ReportResource($report));
    }

    /**
     * Cancel report meeting
     */
    #[Group('Report')]
    #[ExcludeRouteFromDocs]
    public function cancel(CloseReportRequest $request, Counseling $report)
    {
        $report->update([
            'result' => $request->result,
            'status' => CounselingStatus::DIBATALKAN->value,
        ]);
        $report->load(['student', 'counselor']);

        return $this->created(new ReportResource($report));
    }

    /**
     * Get report and sharing count graph
     */
    #[Group('Dashboard')]
    public function getReportGraph()
    {
        Gate::authorize('viewGraph', Counseling::class);
        if (Auth::user()->role === UserRole::HEADTEACHER->value) {
            $report = Counseling::where('source_type', 'student')
                ->whereIn('student_id', Auth::user()->school->students->pluck('id'))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $sharing = Sharing::whereIn('user_id', Auth::user()->school->students->pluck('id'))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');
        } else {
            $report = Counseling::where('source_type', 'student')
                ->whereIn('student_id', Auth::user()->counselored->pluck('id'))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            $sharing = Sharing::whereIn('user_id', Auth::user()->counselored->pluck('id'))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');
        }

        return $this->success([
            'report' => (object) $report,
            'sharing' => (object) $sharing,
        ]);
    }

    /**
     * Get latest 2 report
     */
    #[Group('Notification')]
    #[ExcludeRouteFromDocs]
    public function latestOfStudent()
    {
        Gate::authorize('viewLatest', Counseling::class);
        $reports = Counseling::where('source_type', 'student')
            ->with(['counselor'])
            ->where('student_id', Auth::id())
            ->latest()
            ->take(2)
            ->get();

        return $this->success(ReportResource::collection($reports));
    }

    /**
     * Schedule a meeting from a report
     */
    #[Group('Report')]
    public function scheduleMeeting(Request $request, Counseling $report, ScheduleReportCounselingAction $action)
    {
        Gate::authorize('scheduleMeeting', $report);

        $validated = $request->validate([
            'proposed_date' => 'required|date_format:Y-m-d',
            'proposed_time' => 'required|date_format:H:i',
            'room' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $counseling = $action->handle($report, $validated);

        return $this->success(new CounselingResource($counseling));
    }
}
