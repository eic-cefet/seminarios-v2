<?php

namespace App\Providers;

use App\Models\Registration;
use App\Models\Seminar;
use App\Models\SeminarLocation;
use App\Models\Subject;
use App\Models\User;
use App\Policies\RegistrationPolicy;
use App\Policies\SeminarLocationPolicy;
use App\Policies\SeminarPolicy;
use App\Policies\SubjectPolicy;
use App\Policies\UserPolicy;
use App\Services\AiService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiService::class, fn () => AiService::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            $maxAttempts = (int) env('LOGIN_RATE_LIMIT', 5);

            return Limit::perMinute($maxAttempts)->by(
                $request->input('email').'|'.$request->ip()
            );
        });

        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        Gate::policy(Seminar::class, SeminarPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Subject::class, SubjectPolicy::class);
        Gate::policy(SeminarLocation::class, SeminarLocationPolicy::class);
        Gate::policy(Registration::class, RegistrationPolicy::class);

        Scramble::registerUiRoute('api/external/docs');
        Scramble::registerJsonSpecificationRoute('api/external/docs.json');

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );

            // Clean up names — remove "External" prefix from controller-derived tags and schemas
            $tagRenames = [
                'ExternalSeminar' => 'Seminars',
                'ExternalLocation' => 'Seminar Locations',
                'ExternalSeminarType' => 'Seminar Types',
            ];

            foreach ($openApi->paths as $path) {
                foreach ($path->operations as $operation) {
                    $operation->tags = array_map(
                        fn (string $tag) => $tagRenames[$tag] ?? $tag,
                        $operation->tags,
                    );
                }
            }

            // Rename request schemas: swap FQCN key with a short key so class_basename() returns the clean name
            $schemaRenames = [
                \App\Http\Requests\External\ExternalSeminarStoreRequest::class => 'SeminarStoreRequest',
                \App\Http\Requests\External\ExternalSeminarUpdateRequest::class => 'SeminarUpdateRequest',
            ];

            foreach ($schemaRenames as $fqcn => $newName) {
                if (isset($openApi->components->schemas[$fqcn])) {
                    $openApi->components->schemas[$newName] = $openApi->components->schemas[$fqcn];
                    unset($openApi->components->schemas[$fqcn]);
                    unset($openApi->components->tempNames[class_basename($fqcn)]);
                }
            }
        });
    }
}
