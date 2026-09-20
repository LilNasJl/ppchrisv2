<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveApprovalEvent;
use App\Models\LeaveRequestApproval;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveApprovalNotifier
{
    public function current(Leave $leave, LeaveApprovalEvent $event, string $title = 'Leave request awaiting your review'): void
    {
        $step = $leave->approvalSteps()->where('sequence', $leave->current_approval_order)->first();
        if ($step?->is_hr || ! $leave->approval_workflow_id) {
            $users = User::whereIn('role', ['hr', 'admin'])->where('is_disabled', false)->get()
                ->filter(fn (User $user) => LeaveApprovalAccess::review($user));
        } else {
            $users = collect([$step?->approver?->user])->filter(fn ($user) => $user && ! $user->is_disabled);
        }
        foreach ($users as $user) {
            $this->queue($user, $event, $title, ($leave->approval_snapshot['employee_name'] ?? $leave->employee?->full_name).' - '.$leave->leave_type);
        }
    }

    public function requester(Leave $leave, LeaveApprovalEvent $event, string $title): void
    {
        if ($user = $leave->employee?->user) {
            $this->queue($user, $event, $title, $event->remarks ?: $event->action);
        }
    }

    private function queue(User $user, LeaveApprovalEvent $event, string $title, string $body): void
    {
        DB::table('leave_approval_deliveries')->insertOrIgnore([
            'id' => (string) Str::uuid(), 'event_id' => $event->id, 'user_id' => $user->id,
            'payload' => json_encode(Notification::make()->title($title)->body($body)->info()->getDatabaseMessage()),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::afterCommit(function (): void {
            try {
                $this->deliver();
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    public function deliver(): void
    {
        DB::table('leave_approval_deliveries')->whereNull('delivered_at')->orderBy('created_at')->limit(200)->pluck('id')
            ->each(function (string $id): void {
                DB::transaction(function () use ($id): void {
                    $delivery = DB::table('leave_approval_deliveries')->where('id', $id)->lockForUpdate()->first();
                    if (! $delivery || $delivery->delivered_at) {
                        return;
                    }
                    if ($user = User::find($delivery->user_id)) {
                        // A stable notification ID makes retries safe even after a process interruption.
                        $user->notifications()->firstOrCreate(['id' => $id], [
                            'type' => \Filament\Notifications\DatabaseNotification::class,
                            'data' => json_decode($delivery->payload, true, flags: JSON_THROW_ON_ERROR),
                        ]);
                    }
                    DB::table('leave_approval_deliveries')->where('id', $id)->update(['delivered_at' => now()]);
                });
            });
    }

    public function remind(): void
    {
        LeaveRequestApproval::where('status', 'Pending')->where('activated_at', '<=', now()->subHours(48))
            ->where(fn ($q) => $q->whereNull('reminded_at')->orWhere('reminded_at', '<=', now()->subDay()))
            ->pluck('leave_id')->unique()->each(function ($id): void {
                DB::transaction(function () use ($id): void {
                    $leave = Leave::lockForUpdate()->find($id);
                    if (! $leave || $leave->status !== 'Pending') {
                        return;
                    }
                    $step = $leave->approvalSteps()->where('status', 'Pending')->lockForUpdate()->first();
                    if (! $step || $step->activated_at?->gt(now()->subHours(48)) || $step->reminded_at?->gt(now()->subDay())) {
                        return;
                    }
                    $step->update(['reminded_at' => now()]);
                    $event = app(LeaveApprovalService::class)->event($leave, null, 'Approval reminder', null, $step);
                    $this->current($leave, $event, 'Leave approval pending for more than 48 hours');
                    if (! $step->is_hr && ! LeaveApprovalAccess::employee($step->approver)) {
                        foreach (User::whereIn('role', ['hr', 'admin'])->where('is_disabled', false)->get() as $user) {
                            if (LeaveApprovalAccess::override($user)) {
                                $this->queue($user, $event, 'Leave approver needs reassignment', $step->label.' - '.$step->approver_name);
                            }
                        }
                    }
                });
            });
        $this->deliver();
    }
}
