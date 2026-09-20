<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_approval_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            foreach (['employee', 'branch', 'department', 'designation'] as $scope) {
                $table->unsignedBigInteger($scope.'_id')->nullable()->index();
            }
            $table->string('active_scope_key')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('leave_approval_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('leave_approval_workflows')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label', 100);
            $table->unsignedBigInteger('approver_employee_id')->index();
            $table->unsignedBigInteger('alternate_employee_id')->nullable();
            $table->unique(['workflow_id', 'sequence']);
        });
        Schema::create('leave_workflow_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('leave_approval_workflows')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('configuration');
            $table->timestamps();
            $table->unique(['workflow_id', 'version']);
        });
        Schema::table('leaves', function (Blueprint $table): void {
            $table->foreignId('approval_workflow_id')->nullable()->constrained('leave_approval_workflows')->restrictOnDelete();
            $table->unsignedInteger('approval_workflow_version')->nullable();
            $table->json('approval_snapshot')->nullable();
            $table->string('approval_phase', 30)->nullable()->index();
            $table->unsignedInteger('current_approval_order')->nullable();
        });
        Schema::create('leave_request_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->restrictOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label', 100);
            $table->boolean('is_hr')->default(false);
            $table->unsignedBigInteger('approver_employee_id')->nullable()->index();
            $table->string('approver_name')->nullable();
            $table->string('status', 30)->default('Waiting');
            $table->unsignedBigInteger('acted_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();
            $table->unique(['leave_id', 'sequence']);
            $table->index(['approver_employee_id', 'status']);
        });
        Schema::create('leave_approval_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->restrictOnDelete();
            $table->unsignedBigInteger('step_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name');
            $table->string('action', 60);
            $table->text('remarks')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
        Schema::create('leave_approval_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained('leave_approval_events')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->json('payload');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        // Preserve the existing direct-HR route until administrators configure specific flows.
        $id = DB::table('leave_approval_workflows')->insertGetId([
            'name' => 'Company default - HR review', 'is_active' => true, 'version' => 1,
            'active_scope_key' => '0:0:0:0', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('leave_workflow_revisions')->insert([
            'workflow_id' => $id, 'version' => 1,
            'configuration' => json_encode(['name' => 'Company default - HR review', 'levels' => [], 'reason' => 'Initial direct-HR default']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (Schema::hasTable('permissions')) {
            foreach (['Manage:LeaveWorkflow', 'Review:Leave', 'Override:Leave'] as $name) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approval_deliveries');
        Schema::dropIfExists('leave_approval_events');
        Schema::dropIfExists('leave_request_approvals');
        Schema::table('leaves', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approval_workflow_id');
            $table->dropColumn(['approval_workflow_version', 'approval_snapshot', 'approval_phase', 'current_approval_order']);
        });
        Schema::dropIfExists('leave_workflow_revisions');
        Schema::dropIfExists('leave_approval_levels');
        Schema::dropIfExists('leave_approval_workflows');
    }
};
