<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Events\ReportCreated;
use App\Http\Requests\CloseReportRequest;
use App\Http\Requests\ConfirmReportRequest;
use App\Http\Requests\CreateReportRequest;
use App\Http\Requests\RescheduleReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    use ApiResponder;

    /**
     * Get all report data
     *
     * Get latest reports data belongs to student for student or belongs to all student of counselored for counselor
     */
    #[Group('Report')]
    public function index()
    {
        $user = Auth::user();
        if ($user->role == UserRole::STUDENT->value) {
            $reports = $user->report()->with(['counselor', 'user'])->orderBy('date')->get();
        } elseif ($user->role == UserRole::COUNSELOR->value) {
            $reports = Report::with(['counselor', 'user', 'user.room'])->whereIn('user_id', $user->counselored->pluck('id'))->get();
        } else {
            $reports = collect();
        }

        return $this->success(ReportResource::collection($reports));
    }

    /**
     * Get report today count
     *
     * Mendapatkan data jumlah konseling berdasarkan tipenya
     */
    #[Group('Report')]
    public function getReportCount()
    {
        Gate::authorize('viewGraph', Report::class);
        $user = Auth::user();
        $reports = Report::whereCreatedAt(Carbon::today())->whereIn('user_id', $user->counselored->pluck('id'))->get();

        $countsByStatusArray = $reports->countBy('status')->toArray();
        $statuses = ReportStatus::cases();
        $result = [];
        foreach ($statuses as $status) {
            $result[$status->value] = (int) ($countsByStatusArray[$status->value] ?? 0);
        }

        return $this->success($result);
    }

    /**
     * Create new report
     */
    #[Group('Report')]
    public function store(CreateReportRequest $request)
    {
        $report = Report::create($request->validated());
        ReportCreated::dispatch($report);

        return $this->created(new ReportResource($report));
    }

    /**
     * Get report detail
     */
    #[Group('Report')]
    public function show(Report $report)
    {
        Gate::authorize('view', $report);

        return $this->created(new ReportResource($report));
    }

    /**
     * Get all report of student
     *
     * Mendapatkan semua konseling seorang siswa. Hanya bisa diakses oleh Super Admin
     */
    #[Group('Report')]
    public function reportOfStudent(User $user)
    {
        Gate::authorize('viewStudentReports', [Report::class, $user]);
        $reports = Report::with('counselor')->whereUserId($user->id)->get();

        return $this->created(ReportResource::collection($reports));
    }

    /**
     * Confirm report meeting
     */
    #[Group('Report')]
    public function confirm(ConfirmReportRequest $request, Report $report)
    {
        $report->update($request->validated());

        return $this->created(new ReportResource($report));
    }

    /**
     * Reschedule report meeting
     */
    #[Group('Report')]
    public function reschedule(RescheduleReportRequest $request, Report $report)
    {
        $report->update($request->validated());

        return $this->created(new ReportResource($report));
    }

    /**
     * Finish report meeting
     */
    #[Group('Report')]
    public function close(CloseReportRequest $request, Report $report)
    {
        $report->update(['result' => $request->result, 'status' => ReportStatus::SELESAI->value]);

        return $this->created(new ReportResource($report));
    }

    /**
     * Cancel report meeting
     */
    #[Group('Report')]
    public function cancel(CloseReportRequest $request, Report $report)
    {
        $report->update(['result' => $request->result, 'status' => ReportStatus::DIBATALKAN->value]);

        return $this->created(new ReportResource($report));
    }

    /**
     * Get report and sharing count graph
     *
     * @response array{
     *   data: array{
     *     report: array<string, int>,
     *     sharing: array<string, int>
     *   }
     * }
     */
    #[Group('Dashboard')]
    public function getReportGraph()
    {
        Gate::authorize('viewGraph', Report::class);
        $report = Report::whereIn('user_id', Auth::user()->counselored->pluck('id'))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $sharing = \App\Models\Sharing::whereIn('user_id', Auth::user()->counselored->pluck('id'))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        return $this->success([
            'report' => (object) $report,
            'sharing' => (object) $sharing,
        ]);
    }

    /**
     * Get latest 2 report
     */
    #[Group('Notification')]
    public function latestOfStudent()
    {
        Gate::authorize('viewLatest', Report::class);
        $reports = Report::with(['counselor'])->whereUserId(Auth::id())->latest()->take(2)->get();

        return $this->success(ReportResource::collection($reports));
    }
}
