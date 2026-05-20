<?php

namespace App\Http\Controllers;

use App\Actions\BuildMoodRecapAction;
use App\Actions\StoreMoodRecordAction;
use App\Enums\MoodStatus;
use App\Enums\UserRole;
use App\Exports\AllMoodExport;
use App\Exports\StudentMoodExport;
use App\Http\Requests\MoodRecordSendRequest;
use App\Http\Resources\MoodRecordResource;
use App\Models\MoodRecord;
use App\Models\School;
use App\Models\User;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MoodRecordController extends Controller
{
    use ApiResponder, AuthorizesRequests;

    /**
     * Is user can record today's mood
     *
     * Mengecek apakah murid bisa melakukan rekam mood hari ini
     */
    #[Group('Mood Record')]
    public function check()
    {
        $mood = MoodRecord::where('user_id', Auth::id())->where('recorded', Carbon::today())->get();

        return $this->success(['can' => $mood->count() == 0]);
    }

    /**
     * Get all mood records of student
     *
     * Mendapatkan semua data mood seorang siswa
     */
    #[Group('Mood Record')]
    public function recordsOfStudent(User $user)
    {
        $mood = $user->mood()->orderBy('recorded', 'desc')->get();

        return $this->success(MoodRecordResource::collection($mood));
    }

    /**
     * Check user's mood today
     *
     * Mengecek status mood siswa hari ini
     */
    #[Group('Mood Record')]
    public function today()
    {
        $mood = MoodRecord::where('user_id', Auth::id())->where('recorded', Carbon::today())->first();
        if ($mood) {
            return $this->success(['type' => $mood->status, 'status' => MoodStatus::from($mood->status)->isSecure() ? 'secure' : 'insecure']);
        }

        return $this->error("User doesn't have mood record today", 404, null);
    }

    /**
     * Check user's streak point
     *
     * Menghitung poin streak
     */
    #[Group('Mood Record')]
    public function streaks()
    {
        // Ambil semua tanggal mood dalam 1 query, urutkan descending
        $dates = MoodRecord::forUser(Auth::id())
            ->orderBy('recorded', 'desc')
            ->pluck('recorded')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values();

        $streak = 0;
        $expected = Carbon::today()->toDateString();

        foreach ($dates as $date) {
            if ($date === $expected) {
                $streak++;
                $expected = Carbon::parse($expected)->subDay()->toDateString();
            } else {
                break;
            }
        }

        return $this->success(['streak' => $streak]);
    }

    /**
     * Get user mood recaps by month
     *
     * Mendapatkan rekapitulasi rekaman mood milik murid secara bulanan. Hanya bisa diakses oleh murid
     *
     * @param  string  $month  YYYY-MM ex. 2025-09
     */
    #[Group('Mood Record')]
    public function recapPerMonth(string $month)
    {
        Gate::authorize('recapPerMonth', MoodRecord::class);
        $mood = MoodRecord::where('user_id', Auth::id())->where('recorded', 'like', "$month-__")->orderBy('recorded')->get();

        return $this->success(MoodRecordResource::collection($mood));
    }

    /**
     * Record mood the authenticated user
     *
     * Merekam mood siswa pada hari ini dan akan mengembalikan status serta quotes
     */
    #[Group('Mood Record')]
    public function store(MoodRecordSendRequest $request, StoreMoodRecordAction $action)
    {
        Gate::authorize('store', MoodRecord::class);
        $result = $action->handle($request->validated());

        return $this->created($result, 'Success record mood');
    }

    /**
     * Get mood trends a year
     *
     * Mendapatkan trend mood dalam satu tahun
     */
    #[Group('Mood Record')]
    public function getMoodTrend()
    {
        Gate::authorize('viewSchoolTrend', MoodRecord::class);

        $moods = MoodRecord::selectRaw('MONTH(recorded) as month, status, COUNT(*) as total')
            ->groupBy('month', 'status')
            ->orderBy('month')
            ->get();

        // group per bulan
        $grouped = $moods->groupBy('month');
        $result = [];

        foreach ($grouped as $month => $items) {
            $top = $items->sortByDesc('total')->first();
            $result[$this->monthName($month)] = [
                'status' => $top->status,
                'total' => (int) $top->total,
            ];
        }

        return $this->success($result);
    }

    private function monthName($month)
    {
        return \Carbon\Carbon::create()->month($month)->format('F'); // ex: "January"
        // atau 'M' kalau mau singkat: "Jan"
    }

    /**
     * Get mood count graph
     */
    #[Group('Mood Record')]
    public function getMoodGraph()
    {
        Gate::authorize('dashboard-data');
        $moods = MoodRecord::whereRecorded(now()->toDateString())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $this->success([
            MoodStatus::NEUTRAL->value => (int) ($moods[MoodStatus::NEUTRAL->value] ?? 0),
            MoodStatus::SAD->value => (int) ($moods[MoodStatus::SAD->value] ?? 0),
            MoodStatus::HAPPY->value => (int) ($moods[MoodStatus::HAPPY->value] ?? 0),
            MoodStatus::ANGRY->value => (int) ($moods[MoodStatus::ANGRY->value] ?? 0),
        ]);
    }

    /**
     * Get mood history of the student
     *
     * Mendapatkan rekapitulasi rekam mood siswa berdasarkan username siswa tersebut. Tersedia opsi bulanan dan mingguan (terakhir). Hanya bisa diakses oleh BK maupun Wali dari siswa tersebut
     *
     * @param  string  $type  weekly | monthly
     *
     * @response array{
     *   data: array{
     *     recap: array{
     *       happy: int,
     *       angry: int,
     *       sad: int,
     *       neutral: int
     *     },
     *     mean: "secure"|"insecure",
     *     moods: array<array{
     *       recorded: "2025-08-12",
     *       status: "happy"|"neutral"|"sad"|"angry"
     *     }>,
     *     user: array{
     *       name: string,
     *       phone: string
     *     }
     *   }
     * }
     */
    #[Group('Mood Record')]
    public function moodHistory(
        Request $request,
        User $user,
        string $type,
        BuildMoodRecapAction $recapAction
    ) {
        Gate::authorize('viewHistory', [MoodRecord::class, $user]);

        $query = MoodRecord::where('user_id', $user->id);

        if ($type === 'monthly') {
            $query->whereMonth('recorded', now()->month)->whereYear('recorded', now()->year);
        } elseif ($type === 'weekly') {
            $query->whereBetween('recorded', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        $moods = $query->orderBy('recorded')->get();
        ['recap' => $recap, 'mean' => $mean] = $recapAction->handle($moods);

        return $this->success(compact('recap', 'mean', 'moods', 'user'));
    }

    /**
     * Get mood history of the schools
     *
     * Mendapatkan rekapitulasi rekam mood siswa dalam satu sekolah. Tersedia opsi bulanan dan mingguan (terakhir). Hanya bisa diakses oleh Super Admin
     *
     * @param  string  $type  weekly | monthly
     *
     * @response array{
     *   data: array{
     *     moods: array<array{
     *       recorded: "2025-08-12",
     *       status: "happy"|"neutral"|"sad"|"angry",
     *       total: 1,
     *     }>,
     *     school: array{
     *       name: string,
     *       phone: string
     *     }
     *   }
     * }
     */
    #[Group('Mood Record')]
    public function getMoodTrendSchool(Request $request, School $school, string $type)
    {
        Gate::authorize('viewSchoolTrend', MoodRecord::class);
        $query = MoodRecord::whereIn('user_id', $school->students->pluck('id')->toArray());

        if ($type === 'monthly') {
            $query->whereMonth('recorded', now()->month)
                ->whereYear('recorded', now()->year);
        } elseif ($type === 'weekly') {
            $query->whereBetween('recorded', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        }

        $moods = $query->orderBy('recorded')->get()
            ->groupBy('recorded')
            ->map(function ($items) {
                return $items->groupBy('status')
                    ->map->count()
                    ->sortDesc()
                    ->map(function ($count, $status) use ($items) {
                        return [
                            'recorded' => $items->first()->recorded,
                            'status' => $status,
                            'total' => $count,
                        ];
                    })
                    ->first(); // ambil status dengan jumlah terbanyak
            })
            ->values();

        return $this->success(compact('moods', 'school'));
    }

    /**
     * Export user mood today
     *
     * Mendapatkan laporan excel mood siswa hari ini. bisa diakses oleh wali dan BK
     */
    #[Group('Export')]
    public function exportToday()
    {
        Gate::authorize('export', MoodRecord::class);

        if (Auth::user()->role == UserRole::TEACHER->value) {
            $student = User::whereRole(UserRole::STUDENT->value)->whereMentorId(Auth::id());
        } else {
            $student = User::whereRole(UserRole::STUDENT->value)->whereCounselorId(Auth::id());
        }

        $moods = MoodRecord::with(['user', 'user.room'])->whereIn('user_id', $student->pluck('id'))->where('recorded', Carbon::today())->get()->map(function ($mood) {
            return [
                'name' => $mood->user->name,
                'identifier' => $mood->user->identifier,
                'room' => 'Kelas '.$mood->user->room->level.' '.$mood->user->room->name ?? null,
                'status' => $mood->status,
                'type' => MoodStatus::from($mood->status)->label(),
            ];
        });

        $fileName = 'exports/moods-'.now()->format('Ymd_His').'.xlsx';
        Excel::store(new AllMoodExport($moods), $fileName, 'public');
        $url = Storage::disk('public')->url($fileName);

        return $this->success(compact('url'));
    }

    /**
     * Export user mood weekly
     *
     * Mendapatkan laporan excel mood siswa minggu ini. bisa diakses oleh wali dan BK
     */
    #[Group('Export')]
    public function exportWeekly(string $username, BuildMoodRecapAction $recapAction)
    {
        Gate::authorize('export', MoodRecord::class);

        $user = User::with(['room', 'counselor', 'mentor'])->whereUsername($username)->first();
        $moods = MoodRecord::where('user_id', $user->id)
            ->whereBetween('recorded', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderBy('recorded')
            ->get();

        ['recap' => $recap, 'mean' => $mean] = $recapAction->handle($moods);

        $stud = [
            'name' => $user->name,
            'room' => 'Kelas '.$user->room->level.' '.$user->room->name,
            'counselor' => $user->counselor->name,
            'mentor' => $user->mentor->name,
            'identifier' => $user->identifier,
        ];

        $data = compact('recap', 'mean', 'moods', 'stud');
        $fileName = 'exports/student-mood-'.now()->format('Ymd_His').'.xlsx';
        Excel::store(new StudentMoodExport($data), $fileName, 'public');

        return $this->success(['url' => Storage::disk('public')->url($fileName)]);
    }

    /**
     * Export user mood monthly
     *
     * Mendapatkan laporan excel mood siswa bulan ini. bisa diakses oleh wali dan BK
     */
    #[Group('Export')]
    public function exportMonthly(string $username, BuildMoodRecapAction $recapAction)
    {
        Gate::authorize('export', MoodRecord::class);

        $user = User::with(['room', 'counselor', 'mentor'])->whereUsername($username)->first();
        $moods = MoodRecord::where('user_id', $user->id)
            ->whereMonth('recorded', now()->month)
            ->whereYear('recorded', now()->year)
            ->orderBy('recorded')
            ->get();

        ['recap' => $recap, 'mean' => $mean] = $recapAction->handle($moods);

        $stud = [
            'name' => $user->name,
            'room' => 'Kelas '.$user->room->level.' '.$user->room->name,
            'counselor' => $user->counselor->name,
            'mentor' => $user->mentor->name,
            'identifier' => $user->identifier,
        ];

        $data = compact('recap', 'mean', 'moods', 'stud');
        $fileName = 'exports/student-mood-monthly-'.now()->format('Ymd_His').'.xlsx';
        Excel::store(new StudentMoodExport($data), $fileName, 'public');

        return $this->success(['url' => Storage::disk('public')->url($fileName)]);
    }
}
