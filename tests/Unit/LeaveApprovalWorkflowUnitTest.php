<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApprovalWorkflow;
use App\Models\LeaveRequestApproval;
use App\Models\User;
use App\Services\LeaveApprovalAccess;
use Tests\TestCase;

class LeaveApprovalWorkflowUnitTest extends TestCase
{
    public function test_workflow_specificity_calculation_prioritizes_more_specific_scopes(): void
    {
        $defaultFlow = new LeaveApprovalWorkflow([
            'employee_id' => null,
            'designation_id' => null,
            'branch_id' => null,
            'department_id' => null,
        ]);
        $deptFlow = new LeaveApprovalWorkflow([
            'employee_id' => null,
            'designation_id' => null,
            'branch_id' => null,
            'department_id' => 5,
        ]);
        $branchFlow = new LeaveApprovalWorkflow([
            'employee_id' => null,
            'designation_id' => null,
            'branch_id' => 10,
            'department_id' => null,
        ]);
        $designationFlow = new LeaveApprovalWorkflow([
            'employee_id' => null,
            'designation_id' => 20,
            'branch_id' => null,
            'department_id' => null,
        ]);
        $branchDesignationFlow = new LeaveApprovalWorkflow([
            'employee_id' => null,
            'designation_id' => 20,
            'branch_id' => 10,
            'department_id' => null,
        ]);
        $employeeFlow = new LeaveApprovalWorkflow([
            'employee_id' => 100,
            'designation_id' => null,
            'branch_id' => null,
            'department_id' => null,
        ]);

        $this->assertSame(0, $defaultFlow->specificity());
        $this->assertSame(1, $deptFlow->specificity());
        $this->assertSame(10, $branchFlow->specificity());
        $this->assertSame(100, $designationFlow->specificity());
        $this->assertSame(110, $branchDesignationFlow->specificity());
        $this->assertSame(1000, $employeeFlow->specificity());

        $list = collect([
            $defaultFlow,
            $employeeFlow,
            $deptFlow,
            $branchDesignationFlow,
            $branchFlow,
            $designationFlow,
        ])->sortByDesc(fn (LeaveApprovalWorkflow $flow) => $flow->specificity())->values();

        $this->assertSame($employeeFlow, $list[0]);
        $this->assertSame($branchDesignationFlow, $list[1]);
        $this->assertSame($designationFlow, $list[2]);
        $this->assertSame($branchFlow, $list[3]);
        $this->assertSame($deptFlow, $list[4]);
        $this->assertSame($defaultFlow, $list[5]);
    }

    public function test_leave_is_ready_for_hr_requires_pending_status_and_hr_phase(): void
    {
        // Legacy leave with no workflow id is ready for HR if Pending
        $legacyPending = (new Leave)->forceFill([
            'status' => 'Pending',
            'approval_workflow_id' => null,
        ]);
        $this->assertTrue($legacyPending->isReadyForHr());

        $legacyApproved = (new Leave)->forceFill([
            'status' => 'Approved',
            'approval_workflow_id' => null,
        ]);
        $this->assertFalse($legacyApproved->isReadyForHr());

        // Multi-level leave in preliminary phase is NOT ready for HR
        $preliminaryLeave = (new Leave)->forceFill([
            'status' => 'Pending',
            'approval_workflow_id' => 1,
            'approval_phase' => 'preliminary',
        ]);
        $this->assertFalse($preliminaryLeave->isReadyForHr());

        // Multi-level leave in HR phase with all preliminary steps approved is ready for HR
        $hrReadyLeave = \Mockery::mock(Leave::class)->makePartial();
        $hrReadyLeave->forceFill([
            'status' => 'Pending',
            'approval_workflow_id' => 1,
            'approval_phase' => 'hr',
        ]);

        $stepsQuery = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $hrReadyLeave->shouldReceive('approvalSteps')->andReturn($stepsQuery);
        $stepsQuery->shouldReceive('where')->with('is_hr', false)->andReturnSelf();
        $stepsQuery->shouldReceive('whereNotIn')->with('status', ['Approved', 'Skipped'])->andReturnSelf();
        $stepsQuery->shouldReceive('exists')->andReturn(false);

        $this->assertTrue($hrReadyLeave->isReadyForHr());
    }

    public function test_approval_label_reflects_current_step_or_status(): void
    {
        $approvedLeave = (new Leave)->forceFill([
            'status' => 'Approved',
        ]);
        $this->assertSame('Approved', $approvedLeave->approval_label);

        $rejectedLeave = (new Leave)->forceFill([
            'status' => 'Rejected',
        ]);
        $this->assertSame('Rejected', $rejectedLeave->approval_label);

        $cancelledLeave = (new Leave)->forceFill([
            'status' => 'Cancelled',
        ]);
        $this->assertSame('Cancelled', $cancelledLeave->approval_label);

        $step1 = (new LeaveRequestApproval)->forceFill([
            'sequence' => 1,
            'label' => 'Level 1 (SIC)',
        ]);
        $pendingLeave = (new Leave)->forceFill([
            'status' => 'Pending',
            'current_approval_order' => 1,
        ]);
        $pendingLeave->setRelation('approvalSteps', collect([$step1]));

        $this->assertSame('Pending - Level 1 (SIC)', $pendingLeave->approval_label);
    }

    public function test_leave_approval_access_validates_employee_active_status(): void
    {
        $this->assertFalse(LeaveApprovalAccess::employee(null));

        $endedEmployee = \Mockery::mock(Employee::class)->makePartial();
        $endedEmployee->shouldReceive('trashed')->andReturn(false);
        $endedEmployee->shouldReceive('hasEndedEmployment')->andReturn(true);
        $this->assertFalse(LeaveApprovalAccess::employee($endedEmployee));

        $activeEmployee = \Mockery::mock(Employee::class)->makePartial();
        $activeEmployee->shouldReceive('trashed')->andReturn(false);
        $activeEmployee->shouldReceive('hasEndedEmployment')->andReturn(false);

        // Disabled user
        $disabledUser = (new User)->forceFill([
            'role' => 'employee',
            'is_disabled' => true,
        ]);
        $activeEmployee->setRelation('user', $disabledUser);
        $this->assertFalse(LeaveApprovalAccess::employee($activeEmployee));

        // Non-employee role user
        $hrUser = (new User)->forceFill([
            'role' => 'hr',
            'is_disabled' => false,
        ]);
        $activeEmployee->setRelation('user', $hrUser);
        $this->assertFalse(LeaveApprovalAccess::employee($activeEmployee));

        // Valid active employee user
        $validUser = (new User)->forceFill([
            'role' => 'employee',
            'is_disabled' => false,
        ]);
        $activeEmployee->setRelation('user', $validUser);
        $this->assertTrue(LeaveApprovalAccess::employee($activeEmployee));
    }

    public function test_leave_approval_access_view_authorizes_correct_actors(): void
    {
        // Admin user can view
        $adminUser = (new User)->forceFill(['role' => 'admin', 'is_disabled' => false]);
        $leaveAdmin = (new Leave)->forceFill(['employee_id' => 10, 'status' => 'Pending']);
        $this->assertTrue(LeaveApprovalAccess::view($adminUser, $leaveAdmin));

        // HR user with View:Leave permission can view
        $hrUser = \Mockery::mock(User::class)->makePartial();
        $hrUser->forceFill(['role' => 'hr', 'is_disabled' => false]);
        $hrUser->shouldReceive('can')->with('View:Leave')->andReturn(true);
        $this->assertTrue(LeaveApprovalAccess::view($hrUser, $leaveAdmin));

        // The requester can view
        $requesterUser = (new User)->forceFill(['id' => 1, 'role' => 'employee', 'is_disabled' => false]);
        $requesterEmployee = \Mockery::mock(Employee::class)->makePartial();
        $requesterEmployee->forceFill(['id' => 10]);
        $requesterEmployee->shouldReceive('trashed')->andReturn(false);
        $requesterEmployee->shouldReceive('hasEndedEmployment')->andReturn(false);
        $requesterEmployee->setRelation('user', $requesterUser);
        $requesterUser->setRelation('employee', $requesterEmployee);
        $this->assertTrue(LeaveApprovalAccess::view($requesterUser, $leaveAdmin));

        // Assigned approver can view
        $approverUser = (new User)->forceFill(['id' => 2, 'role' => 'employee', 'is_disabled' => false]);
        $approverEmployee = \Mockery::mock(Employee::class)->makePartial();
        $approverEmployee->forceFill(['id' => 20]);
        $approverEmployee->shouldReceive('trashed')->andReturn(false);
        $approverEmployee->shouldReceive('hasEndedEmployment')->andReturn(false);
        $approverEmployee->setRelation('user', $approverUser);
        $approverUser->setRelation('employee', $approverEmployee);

        $stepsQuery = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $leave = \Mockery::mock(Leave::class)->makePartial();
        $leave->forceFill(['employee_id' => 10, 'status' => 'Pending']);
        $leave->shouldReceive('approvalSteps')->andReturn($stepsQuery);
        $stepsQuery->shouldReceive('where')->with('approver_employee_id', 20)->andReturnSelf();
        $stepsQuery->shouldReceive('whereNotNull')->with('activated_at')->andReturnSelf();
        $stepsQuery->shouldReceive('exists')->andReturn(true);

        $this->assertTrue(LeaveApprovalAccess::view($approverUser, $leave));

        // Unrelated employee cannot view
        $otherUser = (new User)->forceFill(['id' => 3, 'role' => 'employee', 'is_disabled' => false]);
        $otherEmployee = \Mockery::mock(Employee::class)->makePartial();
        $otherEmployee->forceFill(['id' => 99]);
        $otherEmployee->shouldReceive('trashed')->andReturn(false);
        $otherEmployee->shouldReceive('hasEndedEmployment')->andReturn(false);
        $otherEmployee->setRelation('user', $otherUser);
        $otherUser->setRelation('employee', $otherEmployee);

        $stepsQuery2 = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $leave2 = \Mockery::mock(Leave::class)->makePartial();
        $leave2->forceFill(['employee_id' => 10, 'status' => 'Pending']);
        $leave2->shouldReceive('approvalSteps')->andReturn($stepsQuery2);
        $stepsQuery2->shouldReceive('where')->with('approver_employee_id', 99)->andReturnSelf();
        $stepsQuery2->shouldReceive('whereNotNull')->with('activated_at')->andReturnSelf();
        $stepsQuery2->shouldReceive('exists')->andReturn(false);

        $this->assertFalse(LeaveApprovalAccess::view($otherUser, $leave2));
    }

    public function test_leave_approval_access_review_and_configure(): void
    {
        $adminUser = (new User)->forceFill(['role' => 'admin', 'is_disabled' => false]);
        $employeeUser = (new User)->forceFill(['role' => 'employee', 'is_disabled' => false]);
        $disabledHr = (new User)->forceFill(['role' => 'hr', 'is_disabled' => true]);

        $this->assertTrue(LeaveApprovalAccess::review($adminUser));
        $this->assertFalse(LeaveApprovalAccess::review($employeeUser));
        $this->assertFalse(LeaveApprovalAccess::review($disabledHr));

        $this->assertTrue(LeaveApprovalAccess::configure($adminUser));
        $this->assertFalse(LeaveApprovalAccess::configure($employeeUser));
        $this->assertFalse(LeaveApprovalAccess::configure($disabledHr));

        $hrWithReview = \Mockery::mock(User::class)->makePartial();
        $hrWithReview->forceFill(['role' => 'hr', 'is_disabled' => false]);
        $hrWithReview->shouldReceive('can')->with('Review:Leave')->andReturn(true);
        $this->assertTrue(LeaveApprovalAccess::review($hrWithReview));

        $hrWithConfigure = \Mockery::mock(User::class)->makePartial();
        $hrWithConfigure->forceFill(['role' => 'hr', 'is_disabled' => false]);
        $hrWithConfigure->shouldReceive('can')->with('Manage:LeaveWorkflow')->andReturn(true);
        $this->assertTrue(LeaveApprovalAccess::configure($hrWithConfigure));
    }
}
