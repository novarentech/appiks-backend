<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CounselingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MoodRecordController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SelfHelpController;
use App\Http\Controllers\SharingController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\PsychologistController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentConsentController;
use App\Http\Controllers\StudentBookingController;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::get('coba', [QuestionnaireController::class, 'coba']);
Route::get('/', function () {
    return response()->json('OK');
});
Route::get('user/bulk/template', [UserController::class, 'getTemplate']);
Route::post('login', [AuthController::class, 'login']);
Route::get('tag', [TagController::class, 'index']);
Route::controller(LocationController::class)->group(function () {
    Route::get('province', 'province')->name('province');
    Route::get('city/{province}', 'city')->name('city');
    Route::get('district/{city}', 'district')->name('district');
    Route::get('village/{district}', 'village')->name('village');
});
Route::middleware('auth:api')->group(function () {
    Route::get('self-help/{type}/{user:username}', [SelfHelpController::class, 'getByType'])->whereIn('type', ['Daily Journaling', 'Gratitude Journal', 'Grounding Technique', 'Sensory Relaxation']);
    Route::post('self-help/daily-journaling', [SelfHelpController::class, 'createDaily']);
    Route::post('self-help/gratitude-journaling', [SelfHelpController::class, 'createGratitude']);
    Route::post('self-help/grounding-technique', [SelfHelpController::class, 'createGrounding']);
    Route::post('self-help/sensory-relaxation', [SelfHelpController::class, 'createSensory']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('room-student-count', [RoomController::class, 'roomStudentCount']);
    Route::get('room/school/{school:name}', [RoomController::class, 'roomOfSchool']);
    Route::get('content', [VideoController::class, 'allContents']);
    Route::get('video/tag/{tag}', [VideoController::class, 'getByaTag']);
    Route::get('video/{video:video_id}', [VideoController::class, 'getVideoDetailId']);
    Route::get('article/tag/{tag}', [ArticleController::class, 'getByTag']);
    Route::get('article/{article}', [ArticleController::class, 'getArticle']);
    Route::get('quote/mood', [QuoteController::class, 'getByType']);
    Route::get('quote/daily', [QuoteController::class, 'getDaily']);
    Route::get('check-username', [AuthController::class, 'checkUsername']);
    Route::get('questionnaire', [QuestionnaireController::class, 'getAllQuestionnaires']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('article-update/{article}', [ArticleController::class, 'update']);
    Route::post('questionnaire/{type}', [QuestionnaireController::class, 'analyzeQuestionnaire'])->whereIn('type', ['secure', 'insecure']);
    Route::controller(MoodRecordController::class)->group(function () {
        Route::get('mood_record/recap/{month}', 'recapPerMonth');
        Route::get('mood_record/check', 'check');
        Route::get('mood_record/today', 'today');
        Route::get('mood_record/streaks', 'streaks');
        Route::get('mood-record/student/{user:username}', 'recordsOfStudent');
        Route::get('mood-trends/{school:name}/{type}', 'getMoodTrendSchool')->whereIn('type', ['monthly', 'weekly']);
        Route::get('mood-record/pattern/{user:username}/{type}', 'moodHistory')->whereIn('type', ['monthly', 'weekly']);
        Route::get('mood_record/export/today', 'exportToday');
        Route::get('mood_record/export/{username}/weekly', 'exportWeekly');
        Route::get('mood_record/export/{username}/monthly', 'exportMonthly');
    });
    Route::controller(UserController::class)->group(function () {
        Route::post('user/bulk', 'bulkCreate');
        Route::post('user/admin', 'adminCreate');
        Route::post('user/student', 'studentCreate');
        Route::patch('profile', 'profile');
        Route::patch('edit-user/{user:username}', 'edit');
        Route::patch('edit-profile', 'editProfile');
        Route::delete('user/{user:username}', 'destroy');
    });
    Route::controller(SharingController::class)->group(function () {
        Route::patch('sharing/false-positive/{sharing}', 'falsePositive');
        Route::patch('sharing/reply/{sharing}', 'reply');
        Route::patch('sharing/acknowledge/{sharing}', 'acknowledge');
        Route::post('sharing', 'store');
        Route::get('sharing', 'index');
        Route::get('sharing/student/{user:username}', 'sharingOfStudent');
        Route::get('sharing/{sharing}', 'show');
    });
    Route::controller(ReportController::class)->group(function () {
        Route::patch('report/confirm/{report}', 'confirm');
        Route::patch('report/close/{report}', 'close');
        Route::patch('report/cancel/{report}', 'cancel');
        Route::patch('report/reschedule/{report}', 'reschedule');
        Route::post('report', 'store');
        Route::post('report/{report}/schedule-meeting', 'scheduleMeeting');
        Route::get('report/student/{user:username}', 'reportOfStudent');
        Route::get('report', 'index');
        Route::get('report/{report}', 'show');
    });
    Route::controller(GameController::class)->group(function () {
        Route::get('cirrus', 'cirrus');
        Route::post('buy', 'buy');
        Route::post('claim', 'claim');
    });
    Route::get('room/level', [RoomController::class, 'getLevel']);
    Route::get('room/level/{level}', [RoomController::class, 'byLevel'])->whereIn('level', ['X', 'XI', 'XII']);
    Route::apiResource('room', RoomController::class)->only(['index', 'store', 'update', 'destroy', 'show']);
    Route::apiResource('video', VideoController::class)->except(['show']);
    Route::apiResource('articles', ArticleController::class)->except(['show', 'update']);
    Route::apiResource('quote', QuoteController::class)->except(['update']);
    Route::apiResource('mood_record', MoodRecordController::class)->except(['destroy', 'update']);
    Route::apiResource('school', SchoolController::class);
    Route::apiResource('counseling', CounselingController::class)->only(['store', 'show','index']);
    Route::post('counseling-logs', [CounselingController::class, 'storeLog']);
    Route::post('counseling/{counseling}/consent', [CounselingController::class, 'sendConsent']);
    Route::prefix('admin')->group(function () {
        Route::patch('psychologists/{psychologist}/toggle', [PsychologistController::class, 'toggleStatus']);
        Route::apiResource('psychologists', PsychologistController::class);
    });
    Route::prefix('dashboard')->group(function () {
        Route::get('sharing-count', [SharingController::class, 'getSharingCount']);
        Route::get('report-count', [ReportController::class, 'getReportCount']);
        Route::get('report-graph', [ReportController::class, 'getReportGraph']);
        Route::get('room-count', [RoomController::class, 'getRoomCount']);
        Route::get('mood-trends', [MoodRecordController::class, 'getMoodTrend']);
        Route::get('mood-graph', [MoodRecordController::class, 'getMoodGraph']);
        Route::get('mood-statistics', [MoodRecordController::class, 'moodStatistics']);
        Route::get('latest-content', [VideoController::class, 'getLatestContent']);
        Route::get('today-content', [VideoController::class, 'getTodayContent']);
        Route::controller(DashboardController::class)->group(function () {
            Route::get('super', 'super');
            Route::get('headteacher', 'headteacher');
            Route::get('teacher', 'teacher');
            Route::get('admin', 'admin');
            Route::get('counselor', 'counselor');
            Route::get('content', 'content');
            Route::get('content-statistics', 'contentStatistics');
        });
        Route::controller(UserController::class)->group(function () {
            Route::get('student', 'getStudents');
            Route::get('users', 'getUsers');
            Route::get('users/{username}', 'getUserDetail');
            Route::get('users/type/{type}', 'getUsersByType')->whereIn('type', ['student', 'teacher', 'counselor', 'headteacher', 'admin','psychologist']);
            Route::get('latest-user', 'getLatestUser');
            Route::get('today-user', 'getTodayUser');
            Route::post('users', 'store');
        });
    });

    Route::prefix('notification')->group(function () {
        Route::get('latest-sharing', [SharingController::class, 'latestOfStudent']);
        Route::get('latest-report', [ReportController::class, 'latestOfStudent']);
    });

    // Student digital consent & dashboard routes
    Route::prefix('student')->group(function () {
        Route::get('dashboard/widgets', [StudentDashboardController::class, 'getWidgets']);
        Route::get('counselings', [StudentDashboardController::class, 'getCounselings']);
        Route::get('counselings/{consent}/consent', [StudentConsentController::class, 'show']);
        Route::patch('counselings/{counseling}/acknowledge', [CounselingController::class, 'acknowledge']);
        Route::patch('consents/{consent}', [StudentConsentController::class, 'update']);

        // Booking slot browsing & submission (AND-2)
        Route::get('referrals/{counseling}/available-dates', [StudentBookingController::class, 'availableDates']);
        Route::get('referrals/{counseling}/available-slots', [StudentBookingController::class, 'availableSlots']);
        Route::post('bookings', [StudentBookingController::class, 'store']);
        Route::get('bookings/{booking}', [StudentBookingController::class, 'show']);
    });

    // Psychologist schedule management (AND-13 & AND-9)
    Route::prefix('psychologist')->group(function () {
        Route::get('slots', [App\Http\Controllers\PsychologistSlotController::class, 'index']);
        Route::post('slots', [App\Http\Controllers\PsychologistSlotController::class, 'store']);
        Route::delete('slots/{slot}', [App\Http\Controllers\PsychologistSlotController::class, 'destroy']);
        Route::get('referrals', [App\Http\Controllers\PsychologistReferralController::class, 'index']);
        Route::get('referrals-overview', [App\Http\Controllers\PsychologistReferralController::class, 'overview']);
        Route::get('referrals/pending', [App\Http\Controllers\PsychologistReferralController::class, 'pending']);
        Route::patch('referrals/{booking}/decide', [App\Http\Controllers\PsychologistReferralController::class, 'decide']);
        Route::get('referrals/{counseling}/summary', [App\Http\Controllers\PsychologistSummaryController::class, 'getSummary']);
        Route::post('referrals/{counseling}/feedback', [App\Http\Controllers\PsychologistSummaryController::class, 'storeFeedback']);
    });

    // Principal Awareness Dashboard (AND-12)
    Route::prefix('headteacher')->group(function () {
        Route::get('dashboard/stats', [App\Http\Controllers\PrincipalDashboardController::class, 'stats']);
        Route::get('incidents', [App\Http\Controllers\PrincipalDashboardController::class, 'incidents']);
        Route::patch('notifications/{id}/read', [App\Http\Controllers\PrincipalDashboardController::class, 'markNotificationRead']);
    });
});
