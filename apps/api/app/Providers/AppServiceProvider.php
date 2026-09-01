<?php

namespace App\Providers;

use App\Console\Commands\ValidateProductionConfigCommand;
use App\Platform\Shared\Config\ConfigGuardScope;
use App\Platform\Shared\Config\ProductionConfigValidator;
use App\Platform\Shared\Http\TrustedEdgeConfigurator;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerQueueFailureLogging();
        $this->commands([ValidateProductionConfigCommand::class]);
        // Must run here rather than in bootstrap/app.php: config is not bound yet at the moment
        // ->withMiddleware() fires. See TrustedEdgeConfigurator for the measurement.
        TrustedEdgeConfigurator::apply();
        $this->guardProductionConfig();
        $this->configureFilamentAccessibility();
    }

    /**
     * Accessibility: a Filament clickable table row wraps EVERY cell in a record-URL `<a>`. An
     * icon-only column (no text node) therefore renders a link with no discernible name — a serious
     * axe `link-name` violation. Icon columns here are decorative status glyphs, so disable their
     * per-cell click globally: the row stays navigable via its text columns and no nameless link is
     * emitted. Applied as a default before each resource's own column chain, so any resource that
     * genuinely needs a clickable icon can still re-enable it with `->disableClick(false)`.
     */
    private function configureFilamentAccessibility(): void
    {
        IconColumn::configureUsing(function (IconColumn $column): void {
            $column->disableClick();
        });
    }

    /**
     * Fail fast: a production process must not do production work on an unsafe configuration.
     *
     * Applies to the HTTP path AND to the long-running queue/schedule workers — see
     * ConfigGuardScope for which processes are in scope and why the operator tooling is not.
     * No-op outside production, so local/testing are unaffected.
     */
    private function guardProductionConfig(): void
    {
        $this->app->booted(function (): void {
            if (! ConfigGuardScope::appliesTo($this->app)) {
                return;
            }

            $errors = $this->app->make(ProductionConfigValidator::class)->criticalErrors();
            if ($errors !== []) {
                throw new RuntimeException('Unsafe production configuration: '.implode(' | ', $errors));
            }
        });
    }

    /**
     * Dead-letter visibility (Sprint 5): a single, domain-agnostic hook that records EVERY job that
     * exhausts its retries and lands in `failed_jobs`. Before this the queue had no generic failure
     * signal — a job could die permanently and nothing outside its own `failed()` handler noticed.
     * Logged at error level (structured, metadata only) so the container log channel surfaces it to
     * alerting. It observes the queue; it changes no job behavior and touches no domain code.
     */
    private function registerQueueFailureLogging(): void
    {
        Queue::failing(function (JobFailed $event): void {
            $job = $event->job;

            Log::error('queue.job_failed', [
                'job' => $job->resolveName(),
                'connection' => $event->connectionName,
                'queue' => $job->getQueue(),
                'uuid' => $job->uuid(),
                'attempts' => $job->attempts(),
                'exception_class' => $event->exception::class,
                'exception_message' => $event->exception->getMessage(),
            ]);
        });
    }
}
