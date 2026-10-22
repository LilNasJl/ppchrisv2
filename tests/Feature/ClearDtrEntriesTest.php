<?php

namespace Tests\Feature;

use App\Filament\Pages\ClearDtrEntries;
use App\Filament\Pages\Dtr as HrDtr;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\DtrSubmission;
use App\Models\EmployeeVisibleDtr;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\DtrBulkClearService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClearDtrEntriesTest extends TestCase
{
    use RefreshDatabase;

    private function branch(string $name): Branch
    {
        return Branch::query()->create([
            'branch_name' => $name,
            'branch_address' => 'Test',
            'mobile_no' => '09123456789',
            'employee_id' => 0,
            'no_of_shifts' => 1,
        ]);
    }

    private function period(string $name, bool $locked = false): PayrollPeriod
    {
        return PayrollPeriod::query()->create([
            'title' => $name,
            'date_start' => $locked ? '2026-09-01' : '2026-10-01',
            'date_end' => $locked ? '2026-09-15' : '2026-10-15',
            'date_payout' => $locked ? '2026-09-20' : '2026-10-20',
            'is_locked' => $locked,
        ]);
    }

    private function entries(PayrollPeriod $period, Branch $branch, string $fingerprint, ?DtrSubmission $submission = null): array
    {
        $data = [
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'fingerprint_id' => $fingerprint,
            'date_in' => $period->date_start->toDateString(),
            'schedule_type' => 'Regular',
            'on_field_dtr_submission_id' => $submission?->id,
        ];

        return [Dtr::query()->create($data), EmployeeVisibleDtr::query()->create($data)];
    }

    public function test_page_lists_only_open_periods_and_links_from_dtr_actions(): void
    {
        $this->period('Open October');
        $this->period('Locked September', true);
        $this->branch('Tagum Station');
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();

        $this->actingAs($admin)->get(HrDtr::getUrl())
            ->assertOk()
            ->assertSee('Saturday WFH Policy')
            ->assertSee('Clear DTR Entries');

        $this->actingAs($admin)->get(ClearDtrEntries::getUrl())
            ->assertOk()
            ->assertSee('Open October')
            ->assertDontSee('Locked September')
            ->assertSee('Tagum Station');
    }

    public function test_clear_removes_only_selected_scope_and_marks_generated_on_field_entries(): void
    {
        $branch = $this->branch('Tagum Station');
        $otherBranch = $this->branch('Maco Station');
        $period = $this->period('Open October');
        $otherPeriod = $this->period('Other October');
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();

        [$official, $visible] = $this->entries($period, $branch, '1001');
        $submission = DtrSubmission::query()->create([
            'payroll_period_id' => $period->id,
            'branch_id' => $branch->id,
            'file_path' => 'proofs/test.pdf',
            'file_name' => 'test.pdf',
            'submission_type' => DtrSubmission::TYPE_PROOF,
            'status' => DtrSubmission::STATUS_APPROVED,
        ]);
        [$onFieldOfficial, $onFieldVisible] = $this->entries($period, $branch, '1002', $submission);
        $submission->forceFill([
            'generated_dtr_id' => $onFieldOfficial->id,
            'generated_visible_dtr_id' => $onFieldVisible->id,
        ])->save();
        [$otherOfficial, $otherVisible] = $this->entries($period, $otherBranch, '2001');
        [$laterOfficial, $laterVisible] = $this->entries($otherPeriod, $branch, '3001');

        $page = Livewire::actingAs($admin)->test(ClearDtrEntries::class)
            ->set('periodId', (string) $period->id)
            ->set('branchId', (string) $branch->id)
            ->assertSee('2')
            ->assertSee('Current password');
        $challenge = $page->instance()->challenge;
        $this->assertMatchesRegularExpression('/^(?=.*[A-Z])(?=.*[0-9])[A-Z0-9]{8}$/', $challenge);

        $page->set('confirmation', $challenge)
            ->set('currentPassword', (string) (config('auth.master_admin.password') ?: 'masteradmin'))
            ->call('clearEntries')
            ->assertHasNoErrors()
            ->assertSet('confirmation', '')
            ->assertSet('currentPassword', '');
        $this->assertNotSame($challenge, $page->instance()->challenge);

        $this->assertSoftDeleted('dtrs', ['id' => $official->id]);
        $this->assertSoftDeleted('dtrs', ['id' => $onFieldOfficial->id]);
        $this->assertDatabaseMissing('employee_visible_dtrs', ['id' => $visible->id]);
        $this->assertDatabaseMissing('employee_visible_dtrs', ['id' => $onFieldVisible->id]);
        $this->assertDatabaseHas('dtrs', ['id' => $otherOfficial->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $otherVisible->id]);
        $this->assertDatabaseHas('dtrs', ['id' => $laterOfficial->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $laterVisible->id]);
        $this->assertSame(DtrSubmission::STATUS_APPROVED, $submission->fresh()->status);
        $this->assertNotNull($submission->fresh()->generated_dtr_deleted_at);
        $this->assertDatabaseHas('action_histories', [
            'action' => 'dtr_bulk_cleared',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_locked_period_and_locked_records_cannot_be_cleared(): void
    {
        $branch = $this->branch('Tagum Station');
        $period = $this->period('Open October');
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();
        [$official, $visible] = $this->entries($period, $branch, '1001');

        $period->update(['is_locked' => true]);

        $page = Livewire::actingAs($admin)->test(ClearDtrEntries::class)
            ->set('periodId', (string) $period->id)
            ->set('branchId', (string) $branch->id);

        $page->set('confirmation', $page->instance()->challenge)
            ->set('currentPassword', (string) (config('auth.master_admin.password') ?: 'masteradmin'))
            ->call('clearEntries')
            ->assertHasErrors(['periodId']);

        $this->assertDatabaseHas('dtrs', ['id' => $official->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $visible->id]);
        $this->assertDatabaseMissing('action_histories', ['action' => 'dtr_bulk_cleared']);
    }

    public function test_confirmation_and_entry_level_lock_are_required(): void
    {
        $branch = $this->branch('Tagum Station');
        $period = $this->period('Open October');
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();
        [$official, $visible] = $this->entries($period, $branch, '1001');

        $page = Livewire::actingAs($admin)->test(ClearDtrEntries::class)
            ->set('periodId', (string) $period->id)
            ->set('branchId', (string) $branch->id)
            ->set('confirmation', 'AAAAAAAA')
            ->set('currentPassword', (string) (config('auth.master_admin.password') ?: 'masteradmin'))
            ->call('clearEntries')
            ->assertHasErrors(['confirmation'])
            ->assertSet('currentPassword', '');

        Dtr::query()->whereKey($official->id)->update(['is_locked' => true]);

        $page->set('confirmation', $page->instance()->challenge)
            ->set('currentPassword', (string) (config('auth.master_admin.password') ?: 'masteradmin'))
            ->call('clearEntries')
            ->assertHasErrors(['periodId']);

        $this->assertDatabaseHas('dtrs', ['id' => $official->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $visible->id]);
    }

    public function test_incorrect_current_password_cannot_clear_entries(): void
    {
        $branch = $this->branch('Tagum Station');
        $period = $this->period('Open October');
        $admin = User::query()->where('username', 'masteradmin')->firstOrFail();
        [$official, $visible] = $this->entries($period, $branch, '1001');

        $page = Livewire::actingAs($admin)->test(ClearDtrEntries::class)
            ->set('periodId', (string) $period->id)
            ->set('branchId', (string) $branch->id);

        $page->set('confirmation', $page->instance()->challenge)
            ->set('currentPassword', 'wrong-password')
            ->call('clearEntries')
            ->assertHasErrors(['currentPassword'])
            ->assertSet('currentPassword', '');

        $this->assertDatabaseHas('dtrs', ['id' => $official->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $visible->id]);
        $this->assertDatabaseMissing('action_histories', ['action' => 'dtr_bulk_cleared']);
    }

    public function test_non_hr_account_cannot_clear_entries(): void
    {
        $branch = $this->branch('Tagum Station');
        $period = $this->period('Open October');
        [$official, $visible] = $this->entries($period, $branch, '1001');
        $employeeUser = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employeeUser);
        $this->assertFalse(ClearDtrEntries::canAccess());
        $this->expectException(AuthorizationException::class);

        try {
            app(DtrBulkClearService::class)->clear($period->id, $branch->id, $employeeUser);
        } finally {
            $this->assertDatabaseHas('dtrs', ['id' => $official->id, 'deleted_at' => null]);
            $this->assertDatabaseHas('employee_visible_dtrs', ['id' => $visible->id]);
        }
    }
}
