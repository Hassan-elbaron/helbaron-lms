<?php

declare(strict_types=1);

use App\Platform\Shared\Tenancy\Concerns\BelongsToTenant;
use App\Platform\Shared\Tenancy\TenantContext;
use App\Platform\Shared\Tenancy\TenantId;
use App\Platform\Shared\Tenancy\WithoutTenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-tenant leakage suite (A2-S03). Proves the enforcement that the adopted production models
 * inherit (they use the identical BelongsToTenant trait + TenantScope). A tenant-scoped model and
 * a public (un-scoped) model are used so the mechanism is verified in isolation.
 */
class TenantLeakModel extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_leak_test';

    public $timestamps = false;

    protected $guarded = [];
}

class PublicLeakModel extends Model
{
    protected $table = 'public_leak_test';

    public $timestamps = false;

    protected $guarded = [];
}

beforeEach(function (): void {
    Schema::create('tenant_leak_test', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('organization_id')->nullable();
        $table->string('name');
    });
    Schema::create('public_leak_test', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });

    // Seed two tenants (insert bypasses scope + events -> deterministic fixture).
    TenantLeakModel::insert([
        ['organization_id' => 1, 'name' => 'A-1'],
        ['organization_id' => 1, 'name' => 'A-2'],
        ['organization_id' => 2, 'name' => 'B-1'],
    ]);

    app(TenantContext::class)->forget();
});

afterEach(function (): void {
    Schema::dropIfExists('tenant_leak_test');
    Schema::dropIfExists('public_leak_test');
    app(TenantContext::class)->forget();
});

it('Tenant A cannot READ Tenant B', function (): void {
    $bId = TenantLeakModel::query()->where('name', 'B-1')->value('id');

    app(TenantContext::class)->set(TenantId::from(1));

    expect(TenantLeakModel::find($bId))->toBeNull()
        ->and(TenantLeakModel::query()->where('name', 'B-1')->first())->toBeNull();
});

it('Tenant A cannot UPDATE Tenant B', function (): void {
    app(TenantContext::class)->set(TenantId::from(1));

    TenantLeakModel::query()->update(['name' => 'changed']);

    $bName = app(TenantContext::class)->runWithoutTenancy(
        static fn (): ?string => TenantLeakModel::query()->where('organization_id', 2)->value('name'),
    );

    expect($bName)->toBe('B-1'); // Tenant B row untouched.
});

it('Tenant A cannot DELETE Tenant B', function (): void {
    app(TenantContext::class)->set(TenantId::from(1));

    TenantLeakModel::query()->delete();

    $remaining = app(TenantContext::class)->runWithoutTenancy(
        static fn (): int => TenantLeakModel::query()->where('organization_id', 2)->count(),
    );

    expect($remaining)->toBe(1); // Tenant B row survives.
});

it('Tenant A cannot LIST Tenant B', function (): void {
    app(TenantContext::class)->set(TenantId::from(1));

    expect(TenantLeakModel::count())->toBe(2)
        ->and(TenantLeakModel::pluck('name')->all())->toBe(['A-1', 'A-2']);
});

it('Admin/explicit bypass sees all tenants', function (): void {
    app(TenantContext::class)->set(TenantId::from(1));

    $count = app(TenantContext::class)->runWithoutTenancy(
        static fn (): int => TenantLeakModel::count(),
    );

    expect($count)->toBe(3);
});

it('Maintenance mode bypasses tenant scoping', function (): void {
    app(TenantContext::class)->set(TenantId::from(1));

    $maintenance = $this->app->maintenanceMode();
    $maintenance->activate([
        'except' => [], 'redirect' => null, 'retry' => null,
        'refresh' => null, 'secret' => null, 'status' => 503, 'template' => null,
    ]);

    try {
        expect(TenantLeakModel::count())->toBe(3);
    } finally {
        $maintenance->deactivate();
    }
});

it('Queue jobs bypass tenant scoping via WithoutTenancy middleware', function (): void {
    app(TenantContext::class)->set(TenantId::from(1));

    $middleware = new WithoutTenancy;
    $count = $middleware->handle(new stdClass, static fn ($job): int => TenantLeakModel::count());

    expect($count)->toBe(3);
});

it('Public (un-scoped) resources remain public regardless of tenant', function (): void {
    PublicLeakModel::insert([
        ['name' => 'p1'],
        ['name' => 'p2'],
    ]);

    app(TenantContext::class)->set(TenantId::from(1));

    expect(PublicLeakModel::count())->toBe(2);
});
