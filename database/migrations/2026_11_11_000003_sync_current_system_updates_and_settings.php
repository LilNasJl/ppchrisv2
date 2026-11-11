<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncSettings();
        $this->syncUpdates();
    }

    public function down(): void
    {
        // Data sync migration preserves user-authored updates and acknowledgments on rollback.
    }

    private function syncSettings(): void
    {
        if (! Schema::hasTable('system_update_settings')) {
            return;
        }

        $now = now();
        $payload = [
            'popup_frequency' => 'until_acknowledged',
            'show_topbar_button' => true,
            'modal_heading' => 'System Updates & Notes',
            'modal_subtitle' => 'Recent enhancements, attendance & payroll updates, and important operational reminders.',
        ];

        $existing = DB::table('system_update_settings')
            ->orderBy('id')
            ->first();

        if (! $existing) {
            DB::table('system_update_settings')->insert([
                ...$payload,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $needsUpdate = (string) $existing->popup_frequency !== $payload['popup_frequency']
            || (bool) $existing->show_topbar_button !== $payload['show_topbar_button']
            || (string) $existing->modal_heading !== $payload['modal_heading']
            || (string) ($existing->modal_subtitle ?? '') !== $payload['modal_subtitle'];

        if ($needsUpdate) {
            DB::table('system_update_settings')
                ->where('id', $existing->id)
                ->update([
                    ...$payload,
                    'updated_at' => $now,
                ]);
        }
    }

    private function syncUpdates(): void
    {
        if (! Schema::hasTable('system_updates')) {
            return;
        }

        $now = now();

        if (Schema::hasColumn('system_updates', 'deleted_at')) {
            DB::table('system_updates')
                ->where('title', 'HR Review, On Field DTR Safeguards & Payroll Controls')
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        $desiredUpdates = [
            [
                'title' => 'Attendance, Overtime & DTR Filing Policy Updates',
                'summary' => 'Important filing and eligibility updates now active across both HR and Employee Self-Service.',
                'category' => 'policy',
                'audience' => 'all',
                'items' => [
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
                ],
                'notes' => 'Double-check your actual punch logs and cutoff schedule before submitting attendance change requests or overtime filings.',
                'is_pinned' => true,
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Station Approver Overtime Adjustment Control',
                'summary' => 'Updates for Station Managers / SIC / RC reviewing employee DTR change requests.',
                'category' => 'feature',
                'audience' => 'station_manager',
                'items' => [
                    [
                        'type' => 'feature',
                        'title' => 'Editable Requested Overtime (Capped at Actual Excess)',
                        'description' => "First-level approvers can adjust the requested overtime minutes on pending Overtime requests up to the employee's actual recorded excess minutes.",
                    ],
                ],
                'notes' => 'All approved DTR change and overtime requests continue to HR for final approval before payroll crediting.',
                'is_pinned' => false,
                'is_published' => true,
                'sort_order' => 3,
            ],
        ];

        $hasDeletedAt = Schema::hasColumn('system_updates', 'deleted_at');

        foreach ($desiredUpdates as $entry) {
            $encodedItems = json_encode($entry['items'], JSON_THROW_ON_ERROR);

            $existing = DB::table('system_updates')
                ->where('title', $entry['title'])
                ->when($hasDeletedAt, fn ($query) => $query->whereNull('deleted_at'))
                ->orderBy('id')
                ->first();

            if (! $existing) {
                DB::table('system_updates')->insert([
                    'uuid' => (string) Str::uuid(),
                    'version' => null,
                    'title' => $entry['title'],
                    'summary' => $entry['summary'],
                    'category' => $entry['category'],
                    'audience' => $entry['audience'],
                    'items' => $encodedItems,
                    'notes' => $entry['notes'],
                    'is_pinned' => $entry['is_pinned'],
                    'is_published' => $entry['is_published'],
                    'published_at' => $now,
                    'sort_order' => $entry['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $existingItems = json_decode((string) ($existing->items ?? '[]'), true);
            $needsUpdate = (string) ($existing->summary ?? '') !== $entry['summary']
                || (string) $existing->category !== $entry['category']
                || (string) $existing->audience !== $entry['audience']
                || $existingItems !== $entry['items']
                || (string) ($existing->notes ?? '') !== $entry['notes']
                || (bool) $existing->is_pinned !== $entry['is_pinned']
                || (bool) $existing->is_published !== $entry['is_published']
                || (int) $existing->sort_order !== $entry['sort_order'];

            if ($needsUpdate) {
                DB::table('system_updates')
                    ->where('id', $existing->id)
                    ->update([
                        'summary' => $entry['summary'],
                        'category' => $entry['category'],
                        'audience' => $entry['audience'],
                        'items' => $encodedItems,
                        'notes' => $entry['notes'],
                        'is_pinned' => $entry['is_pinned'],
                        'is_published' => $entry['is_published'],
                        'published_at' => $existing->published_at ?? $now,
                        'sort_order' => $entry['sort_order'],
                        'updated_at' => $now,
                    ]);
            }
        }
    }
};
