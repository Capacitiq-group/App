<?php

namespace App\Providers;

use App\Contracts\AI\GeminiClientInterface;
use App\Enums\Common\ModelEntityTypeEnum;
use App\Libraries\Gemini\GeminiClient as AppGeminiClient;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Space;
use App\Models\User;
use App\Services\AI\Copilot\Engines\AnalyticsEngine;
use App\Services\AI\Copilot\Engines\AppKnowledgeEngine;
use App\Services\AI\Copilot\Engines\ContentGenerationEngine;
use App\Services\AI\Copilot\Engines\NavigationEngine;
use App\Services\AI\Copilot\Engines\VideoReviewEngine;
use App\Services\AI\Copilot\Gateway\AiGateway;
use App\Services\AI\Copilot\Orchestrator\CopilotOrchestrator;
use App\Services\Analytics\AnalyticsAnswerBuilder;
use App\Services\Analytics\AnalyticsPlannerService;
use App\Services\Analytics\AnalyticsToolExecutor;
use App\Services\Analytics\MetricsCatalog;
use Gemini\Client as GeminiClient;
use Gemini\Contracts\ClientContract as GeminiClientContract;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeminiClientContract::class, static function (): GeminiClient {
            $apiKey  = (string) config('gemini.api_key', '');
            $baseUrl = (string) config('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
            $timeout = (int)    config('gemini.request_timeout', 30);

            return \Gemini::factory()
                ->withApiKey(apiKey: $apiKey)
                ->withQueryParam(name: 'key', value: $apiKey)
                ->withBaseUrl(baseUrl: $baseUrl)
                ->withHttpClient(client: new GuzzleClient(['timeout' => $timeout]))
                ->make();
        });

        $this->app->singleton(GeminiClientInterface::class, AppGeminiClient::class);

        // AI Gateway + Orchestrator
        $this->app->singleton(AiGateway::class);
        $this->app->singleton(CopilotOrchestrator::class);

        // Copilot engines
        $this->app->singleton(ContentGenerationEngine::class);
        $this->app->singleton(AppKnowledgeEngine::class);
        $this->app->singleton(NavigationEngine::class);
        $this->app->singleton(AnalyticsEngine::class);
        $this->app->singleton(VideoReviewEngine::class);

        // Analytics services
        $this->app->singleton(MetricsCatalog::class);
        $this->app->singleton(AnalyticsPlannerService::class);
        $this->app->singleton(AnalyticsToolExecutor::class);
        $this->app->singleton(AnalyticsAnswerBuilder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fail-fast: never run with debug enabled in production.
        if (App::isProduction() && config('app.debug')) {
            throw new \RuntimeException('APP_DEBUG must be false in production.');
        }

        Model::shouldBeStrict(! App::isProduction());

        Relation::enforceMorphMap([
            ModelEntityTypeEnum::POST->value => Post::class,
            ModelEntityTypeEnum::USER->value => User::class,
            ModelEntityTypeEnum::HASHTAG->value => Hashtag::class,
            ModelEntityTypeEnum::SPACE->value => Space::class,
        ]);

        // This app authenticates API clients with a JWT bearer token (guard
        // 'api'), not Laravel's default session-based 'web' guard.
        // routes/api.php already calls Broadcast::routes() with the correct
        // 'auth:api' + 'check_user_status' middleware — calling it again
        // here would register a duplicate /broadcasting/auth route. All
        // that's needed here is loading the channel authorization callbacks,
        // which nothing else does now that 'channels:' was removed from
        // bootstrap/app.php's withRouting() call.
        require base_path('routes/channels.php');
    }
}
