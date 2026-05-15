<?php

namespace App\Http\Controllers;

use App\Enums\MoodStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\MoodRecord;
use App\Models\Quote;
use App\Models\Report;
use App\Models\School;
use App\Models\Sharing;
use App\Models\User;
use App\Models\Video;
use App\Traits\ApiResponder;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    use ApiResponder;

    /**
     * Dashboard headteacher datas
     *
     * Mendapatkan data hitungan yang diperlukan di dashboard guru kepala sekolah
     */
    #[Group('Dashboard')]
    public function headteacher()
    {
        Gate::allowIf(function (User $user) {
            return $user->role == UserRole::HEADTEACHER->value;
        });
        $school = Auth::user()->school;

        // Hitung user per role langsung di database
        $student_count = $school->users()->whereRole(UserRole::STUDENT->value)->count();
        $teacher_count = $school->users()->whereRole(UserRole::TEACHER->value)->count();
        $counselor_count = $school->users()->whereRole(UserRole::COUNSELOR->value)->count();

        // Hitung jumlah room langsung di query
        $room_count = $school->rooms()->count();

        return $this->success([
            'student_count' => $student_count,
            'teacher_count' => $teacher_count,
            'counselor_count' => $counselor_count,
            'room_count' => $room_count,
        ]);
    }

    /**
     * Dashboard admin for contents
     *
     * Mendapatkan data statistik hitungan jumlah konten
     */
    #[Group('Content')]
    public function contentStatistics()
    {
        Gate::allowIf(function (User $user) {
            return $user->role == UserRole::ADMIN->value;
        });
        $school = Auth::user()
            ->school()
            ->withCount(['videos', 'articles', 'quotes'])
            ->first();

        $video_count = $school->videos_count;
        $article_count = $school->articles_count;
        $quote_count = $school->quotes_count;

        return $this->success([
            'quote_count' => $quote_count,
            'article_count' => $article_count,
            'video_count' => $video_count,
        ]);
    }

    /**
     * Dashboard teacher datas
     *
     * Mendapatkan data hitungan yang diperlukan di dashboard guru wali
     */
    #[Group('Dashboard')]
    public function teacher()
    {
        Gate::allowIf(function (User $user) {
            return $user->role == UserRole::TEACHER->value;
        });
        $student = User::whereRole(UserRole::STUDENT->value)->whereMentorId(Auth::id());
        $student_count = $student->count();
        $moods = MoodRecord::whereIn('user_id', User::where('mentor_id', Auth::id())->pluck('id'))->where('recorded', Carbon::today());

        $mood_today_count = $moods->count();
        $mood_secure_count = $moods->whereIn('status', MoodStatus::secureValues())->count();
        $mood_insecure_count = $moods->whereIn('status', MoodStatus::insecureValues())->count();

        return $this->success([
            'student_count' => (int) $student_count,
            'mood_today_count' => (int) $mood_today_count,
            'mood_secure_count' => (int) $mood_secure_count,
            'mood_insecure_count' => (int) $mood_insecure_count,
        ]);
    }

    /**
     * Dashboard counselor datas
     *
     * Mendapatkan data hitungan yang diperlukan di dashboard guru BK
     */
    #[Group('Dashboard')]
    public function counselor()
    {
        Gate::allowIf(function (User $user) {
            return $user->role == UserRole::COUNSELOR->value;
        });
        $student = User::whereRole(UserRole::STUDENT->value)->whereCounselorId(Auth::id());
        $student_count = $student->count();

        $report_today_count = Report::whereCreatedAt(Carbon::today())->whereIn('user_id', $student->pluck('id')->toArray())->count();
        $meet_today_count = Report::where('date', Carbon::today())->whereIn('user_id', $student->pluck('id')->toArray())->count();
        $sharing_today_count = Sharing::whereCreatedAt(Carbon::today())->whereIn('user_id', $student->pluck('id')->toArray())->count();

        return $this->success([
            'student_count' => (int) $student_count,
            'report_today_count' => (int) $report_today_count,
            'meet_today_count' => (int) $meet_today_count,
            'sharing_today_count' => (int) $sharing_today_count,
        ]);
    }

    /**
     * Dashboard admin datas
     *
     * Mendapatkan data hitungan yang diperlukan di dashboard Admin TU
     */
    #[Group('Dashboard')]
    public function admin()
    {
        Gate::allowIf(function (User $user) {
            return $user->role == UserRole::ADMIN->value;
        });
        $school = Auth::user()->school;

        // Total user
        $users_count = $school->users()->count();

        // Total video & artikel
        $videos_count = $school->videos()->count();
        $articles_count = $school->articles()->count();

        // Total konten hari ini
        $content_today_count = $school->videos()
            ->whereDate('created_at', now())
            ->count()
            + $school->articles()
                ->whereDate('created_at', now())
                ->count();

        return $this->success([
            'users_count' => $users_count,
            'content_count' => $videos_count + $articles_count,
            'content_today_count' => $content_today_count,
        ]);
    }

    /**
     * Dashboard super admin datas
     *
     * Mendapatkan data jumlah sekolah, Admin TU.
     */
    #[Group('Dashboard')]
    public function super()
    {
        Gate::allowIf(function (User $user) {
            return $user->role == UserRole::SUPER->value;
        });
        $school_count = School::count();
        $admin_count = User::whereRole(UserRole::ADMIN->value)->count();

        return $this->success([
            'school_count' => (int) $school_count,
            'admin_count' => (int) $admin_count,
        ]);
    }

    /**
     * Get all content with quotes
     *
     * Mendapatkan semua data videe, artikel, dan quotes di sekolah tersebut
     */
    #[Group('Content')]
    public function content(\App\Actions\GetMixedContentAction $action)
    {
        return $this->success($action->handle(Auth::user()->school_id));
    }
}
