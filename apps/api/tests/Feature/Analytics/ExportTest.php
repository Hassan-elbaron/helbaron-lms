<?php

use App\Domains\Analytics\Enums\ExportStatus;
use App\Domains\Analytics\Export\ExportWriterManager;
use App\Domains\Analytics\Jobs\ProcessExportJob;
use App\Domains\Analytics\Models\ExportJob;
use App\Domains\Analytics\Models\MetricSnapshot;
use App\Domains\Analytics\Models\ReportDefinition;
use App\Domains\Analytics\Services\ExportService;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('queues an async export and produces a downloadable file', function () {
    Storage::fake('local');
    MetricSnapshot::factory()->create(['metric_key' => 'enrollments', 'value' => 9]);
    $report = ReportDefinition::factory()->create(['metric_keys' => ['enrollments']]);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $res = $this->postJson('/api/v1/analytics/exports', ['report' => $report->public_id, 'format' => 'csv'])
        ->assertCreated()->assertJsonPath('data.status', 'queued');

    $job = ExportJob::where('public_id', $res->json('data.id'))->firstOrFail();

    // Run the async job (afterCommit dispatch doesn't fire inside the test transaction).
    (new ProcessExportJob($job->id))->handle(app(ExportService::class), app(ExportWriterManager::class));

    $job->refresh();
    expect($job->status)->toBe(ExportStatus::Completed)
        ->and($job->row_count)->toBe(1)
        ->and(Storage::disk('local')->exists($job->file_path))->toBeTrue();

    $show = $this->getJson("/api/v1/analytics/exports/{$job->public_id}")->assertOk();
    expect($show->json('data.download_url'))->toContain('signature=');
    expect($show->getContent())->not->toContain('file_path');
});
