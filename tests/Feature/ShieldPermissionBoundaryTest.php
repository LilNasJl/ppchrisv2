<?php

namespace Tests\Feature;

use App\Filament\Pages\KpiCategories;
use App\Filament\Pages\KpiConfiguration;
use App\Filament\Pages\LeaveApprovalWorkflows;
use App\Filament\Resources\SystemAccounts\SystemAccountResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ShieldPermissionBoundaryTest extends TestCase
{
    private Role $superAdmin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['permission.cache.store' => 'array']);
        app(PermissionRegistrar::class)->initializeCache();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->boolean('is_disabled')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('branch_name');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        $this->user = User::query()->create([
            'name' => 'Shield Test Admin',
            'email' => 'shield-test@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $this->superAdmin = Role::findOrCreate('super_admin', 'web');
        $this->user->assignRole($this->superAdmin);

        Filament::setCurrentPanel(Filament::getPanel('hr'));
        Filament::auth()->setUser($this->user);
        auth()->setUser($this->user);
    }

    public function test_unchecked_resource_permissions_hide_resources_from_super_admin(): void
    {
        Permission::findOrCreate('ViewAny:Role', 'web');
        Permission::findOrCreate('ViewAny:SystemAccount', 'web');

        $this->assertFalse(Gate::forUser($this->user)->allows('ViewAny:Role'));
        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(SystemAccountResource::canAccess());

        $this->superAdmin->givePermissionTo(['ViewAny:Role', 'ViewAny:SystemAccount']);

        $this->assertTrue(RoleResource::canAccess());
        $this->assertTrue(SystemAccountResource::canAccess());
    }

    public function test_only_active_masteradmin_bypasses_shield_permissions(): void
    {
        Permission::findOrCreate('ViewAny:Role', 'web');
        Permission::findOrCreate('ViewAny:SystemAccount', 'web');
        Permission::findOrCreate('View:KpiConfiguration', 'web');

        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(SystemAccountResource::canAccess());

        $masterAdmin = User::withoutEvents(fn (): User => User::query()->create([
            'name' => 'Master Admin',
            'username' => 'masteradmin',
            'email' => 'masteradmin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]));

        Filament::auth()->setUser($masterAdmin);
        auth()->setUser($masterAdmin);

        $this->assertTrue(RoleResource::canAccess());
        $this->assertTrue(SystemAccountResource::canAccess());
        $this->assertTrue(KpiConfiguration::canAccess());

        $masterAdmin->is_disabled = true;

        $this->assertFalse(Gate::forUser($masterAdmin)->allows('ViewAny:Role'));
        $this->assertFalse($masterAdmin->canAccessPanel(Filament::getPanel('hr')));
    }

    public function test_custom_pages_require_their_own_shield_permission(): void
    {
        Permission::findOrCreate('View:LeaveApprovalWorkflows', 'web');
        Permission::findOrCreate('Manage:LeaveWorkflow', 'web');
        Permission::findOrCreate('View:KpiConfiguration', 'web');
        Permission::findOrCreate('View:KpiCategories', 'web');

        $this->assertFalse(LeaveApprovalWorkflows::canAccess());
        $this->assertFalse(KpiCategories::canAccess());

        $this->superAdmin->givePermissionTo(['View:LeaveApprovalWorkflows', 'View:KpiConfiguration']);

        $this->assertFalse(LeaveApprovalWorkflows::canAccess());
        $this->superAdmin->givePermissionTo('Manage:LeaveWorkflow');

        $this->assertTrue(LeaveApprovalWorkflows::canAccess());
        $this->assertTrue(KpiConfiguration::canAccess());
        $this->assertFalse(KpiCategories::canAccess());

        $this->superAdmin->givePermissionTo('View:KpiCategories');

        $this->assertTrue(KpiCategories::canAccess());
    }
}
