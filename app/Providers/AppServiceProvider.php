<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Events\GeminiTokenUsed;
use App\Events\ReportCreated;
use App\Listeners\RotateGeminiToken;
use App\Listeners\UpdateRelatedSharingPriority;
use App\Models\Cloud;
use App\Models\CounselingLog;
use App\Models\Sharing;
use App\Models\User;
use App\Observers\CloudObserver;
use App\Observers\CounselingLogObserver;
use App\Observers\SharingObserver;
use App\Observers\UserObserver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
        Gate::define('dashboard-data', function (User $user) {
            return $user->role != UserRole::STUDENT->value;
        });
        Gate::policy(\App\Models\PsychologistProfile::class, \App\Policies\PsychologistPolicy::class);

        Event::listen(
            ReportCreated::class,
            UpdateRelatedSharingPriority::class,
        );

        Event::listen(
            GeminiTokenUsed::class,
            RotateGeminiToken::class,
        );
        User::observe(UserObserver::class);
        Cloud::observe(CloudObserver::class);
        Sharing::observe(SharingObserver::class);
        CounselingLog::observe(CounselingLogObserver::class);
    }
}
