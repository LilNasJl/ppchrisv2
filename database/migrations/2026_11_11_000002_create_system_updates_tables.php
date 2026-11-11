<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SYSTEM_UPDATE_PERMISSIONS = [
        'ViewAny:SystemUpdate',
        'View:SystemUpdate',
        'Create:SystemUpdate',
        'Update:SystemUpdate',
        'Delete:SystemUpdate',
        'DeleteAny:SystemUpdate',
        'Restore:SystemUpdate',
        'ForceDelete:SystemUpdate',
        'ForceDeleteAny:SystemUpdate',
        'RestoreAny:SystemUpdate',
        'Replicate:SystemUpdate',
        'Reorder:SystemUpdate',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('system_update_settings')) {
            Schema::create('system_update_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('popup_frequency', 40)->default('until_acknowledged');
                $table->boolean('show_topbar_button')->default(true);
                $table->string('modal_heading')->default('System Updates & Notes');
                $table->string('modal_subtitle')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('system_updates')) {
            Schema::create('system_updates', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->string('version', 50)->nullable();
                $table->string('title');
                $table->text('summary')->nullable();
                $table->string('category', 32)->default('update');
                $table->string('audience', 32)->default('all')->index();
                $table->json('items')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_published')->default(false)->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->integer('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('system_update_acknowledgments')) {
            Schema::create('system_update_acknowledgments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('system_update_id')->constrained('system_updates')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('acknowledged_at');

                $table->unique(['system_update_id', 'user_id'], 'system_update_ack_unique');
                $table->index(['user_id', 'acknowledged_at'], 'system_update_ack_user_idx');
            });
        }

        if (! Schema::hasTable('system_update_user_states')) {
            Schema::create('system_update_user_states', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->timestamp('last_popup_shown_at')->nullable();
                $table->timestamps();
            });
        }

        $this->seedDefaultSettings();
        $this->seedInitialUpdates();
        $this->registerShieldPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('system_update_user_states');
        Schema::dropIfExists('system_update_acknowledgments');
        Schema::dropIfExists('system_updates');
        Schema::dropIfExists('system_update_settings');
    }

    private function seedDefaultSettings(): void
    {
        if (! Schema::hasTable('system_update_settings') || DB::table('system_update_settings')->exists()) {
            return;
        }

        DB::table('system_update_settings')->insert([
            'popup_frequency' => 'until_acknowledged',
            'show_topbar_button' => true,
            'modal_heading' => 'System Updates & Notes',
            'modal_subtitle' => 'Recent enhancements, attendance & payroll updates, and important operational reminders.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedInitialUpdates(): void
    {
        if (! Schema::hasTable('system_updates') || DB::table('system_updates')->exists()) {
            return;
        }

        $now = now();

        DB::table('system_updates')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'version' => 'v2026.11',
                'title' => 'Attendance, Overtime & DTR Filing Policy Updates',
                'summary' => 'Important filing and eligibility updates now active across both HR and Employee Self-Service.',
                'category' => 'policy',
                'audience' => 'all',
                'items' => json_encode([
                    [
                        'type' => 'policy',
                        'title' => '1-Hour Minimum Overtime & Early Clock-In Threshold',
                        'description' => 'Overtime and early clock-in requests now require at least 60 minutes (1 hour). Pending OT requests under the previous 30-minute threshold were voided.',
                    ],
                    [
                        'type' => 'policy',
                        'title' => 'Forgot to Punch Requires Actual Time In / Time Out',
                        'description' => 'Requesters must supply the actual missing Time In and/or Time Out before submitting a Forgot to Punch request.',
                    ],
                    [
                        'type' => 'update',
                        'title' => 'Dark Theme & Mobile Experience',
                        'description' => 'Improved mobile layouts and unified dark-theme styling across DTR, Leave Requests, Loans, Profile, and Management views.',
                    ],
                ], JSON_THROW_ON_ERROR),
                'notes' => 'Double-check your actual punch logs and cutoff schedule before submitting attendance change requests or overtime filings.',
                'is_pinned' => true,
                'is_published' => true,
                'published_at' => $now,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'version' => 'v2026.11',
                'title' => 'HR Review, On Field DTR Safeguards & Payroll Controls',
                'summary' => 'New workflow safeguards, review layout improvements, and performance optimizations for HR administrators.',
                'category' => 'update',
                'audience' => 'hr',
                'items' => json_encode([
                    [
                        'type' => 'update',
                        'title' => 'On Field DTR Locked After Approval or Rejection',
                        'description' => 'Pending On Field DTR requests can be reviewed and edited before decision. Once Approved or Rejected, editing is disabled to preserve DTR and payroll audit integrity.',
                    ],
                    [
                        'type' => 'fix',
                        'title' => 'Accurate Pending Notification Badges',
                        'description' => 'Viewing an On Field DTR request no longer deducts the pending count — counts only clear after Approval or Rejection.',
                    ],
                    [
                        'type' => 'feature',
                        'title' => 'Editable Monthly & Period Payroll Calculation Breakdown',
                        'description' => 'Restored editable period and monthly range breakdowns in Employee Payroll and Payroll Calculation.',
                    ],
                ], JSON_THROW_ON_ERROR),
                'notes' => 'You can configure how often this Updates & Notes modal opens and publish new notices anytime under Settings > Updates & Notes.',
                'is_pinned' => false,
                'is_published' => true,
                'published_at' => $now,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'version' => 'v2026.11',
                'title' => 'Station Approver Overtime Adjustment Control',
                'summary' => 'Updates for Station Managers / SIC / RC reviewing employee DTR change requests.',
                'category' => 'feature',
                'audience' => 'station_manager',
                'items' => json_encode([
                    [
                        'type' => 'feature',
                        'title' => 'Editable Requested Overtime (Capped at Actual Excess)',
                        'description' => 'First-level approvers can adjust the requested overtime minutes on pending Overtime requests up to the employee\'s actual recorded excess minutes.',
                    ],
                ], JSON_THROW_ON_ERROR),
                'notes' => 'All approved DTR change and overtime requests continue to HR for final approval before payroll crediting.',
                'is_pinned' => false,
                'is_published' => true,
                'published_at' => $now,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function registerShieldPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach (self::SYSTEM_UPDATE_PERMISSIONS as $permissionName) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', ['super_admin', 'hr_admin'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::SYSTEM_UPDATE_PERMISSIONS)
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }
};
