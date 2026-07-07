<?php

declare(strict_types=1);

use App\Platform\Shared\Tenancy\Concerns\BelongsToTenant;
use App\Platform\Shared\Tenancy\TenantContext;
use App\Platform\Shared\Tenancy\TenantId;
use App\Platform\Shared\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proves the A2-S02 tenant-enforcement machinery on a throwaway model, without touching any
 * production model (so there is no behavior regression).
 */
class TenantScopeTestModel extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_scope_test';

    public $timestamps = false;

    protected $guarded = [];
}

beforeEach(function (): void {
    Schema::create('tenant_scope_test', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('organization_id')->nullable();
        $table->string('name');
    });

    app(TenantContext::class)->forget();
});

afterEach(function (): void {
    Schema::dropIfExists('tenant_scope_test');
    app(TenantContext::class)->forget();
});

it('does not filter when no tenant is resolved (backward compatible)', function (): void {
    TenantScopeTestModel::insert([
        ['organization_id' => 1, 'name' => 'a'],
        ['organization_id' => 2, 'name' => 'b'],
    ]);

    expect(TenantScopeTestModel::count())->toBe(2);
});

it('filters automatically to the active tenant', function (): void {
    TenantScopeTestModel::insert([
        ['organization_id' => 1, 'name' => 'a'],
        ['organization_id' => 2, 'name' => 'b'],
    ]);

    app(TenantContext::class)->set(TenantId::from(1));

    expect(TenantScopeTestModel::pluck('name')->all())->toBe(['a']);
});

it('assigns the active tenant on create', function (): void {
    app(TenantContext::class)->set(TenantId::from(7));

    $model = TenantScopeTestModel::create(['name' => 'x']);

    expect((int) $model->organization_id)->toBe(7);
});

it('verifies tenant ownership', function (): void {
    app(TenantContext::class)->set(TenantId::from(3));
    $model = TenantScopeTestModel::create(['name' => 'y']);

    expect($model->belongsToTenant(TenantId::from(3)))->toBeTrue()
        ->and($model->belongsToTenant(TenantId::from(4)))->toBeFalse();
});

it('bypasses tenancy within runWithoutTenancy', function (): void {
    TenantScopeTestModel::insert([
        ['organization_id' => 1, 'name' => 'a'],
        ['organization_id' => 2, 'name' => 'b'],
    ]);

    app(TenantContext::class)->set(TenantId::from(1));

    $count = app(TenantContext::class)->runWithoutTenancy(
        static fn (): int => TenantScopeTestModel::count(),
    );

    expect($count)->toBe(2);
});

it('is removable per query via withoutGlobalScope', function (): void {
    TenantScopeTestModel::insert([
        ['organization_id' => 1, 'name' => 'a'],
        ['organization_id' => 2, 'name' => 'b'],
    ]);

    app(TenantContext::class)->set(TenantId::from(1));

    expect(TenantScopeTestModel::withoutGlobalScope(TenantScope::class)->count())->toBe(2);
});

it('supports an explicit forTenant query scope', function (): void {
    TenantScopeTestModel::insert([
        ['organization_id' => 1, 'name' => 'a'],
        ['organization_id' => 2, 'name' => 'b'],
    ]);

    app(TenantContext::class)->runWithoutTenancy(function (): void {
        expect(TenantScopeTestModel::forTenant(TenantId::from(2))->pluck('name')->all())->toBe(['b']);
    });
});
