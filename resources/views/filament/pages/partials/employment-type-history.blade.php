<div class="overflow-x-auto">
    @if ($changes->isEmpty())
        <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
            No employment type changes have been recorded.
        </div>
    @else
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-xs font-semibold uppercase text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <th class="px-3 py-3">Effective Date</th>
                    <th class="px-3 py-3">Previous Type</th>
                    <th class="px-3 py-3">New Type</th>
                    <th class="px-3 py-3">Explanation</th>
                    <th class="px-3 py-3">Changed By</th>
                    <th class="px-3 py-3">Recorded At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($changes as $change)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-3 font-medium text-gray-950 dark:text-white">
                            {{ $change->effective_date?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ $change->previous_type ?: 'Not set' }}
                        </td>
                        <td class="px-3 py-3 font-semibold text-primary-700 dark:text-primary-300">
                            {{ $change->employment_type }}
                        </td>
                        <td class="max-w-sm whitespace-normal px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ $change->explanation ?: 'No explanation provided.' }}
                        </td>
                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">
                            {{ $change->changedBy?->username ?: $change->changedBy?->name ?: 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-gray-500 dark:text-gray-400">
                            {{ $change->created_at?->format('M d, Y h:i A') ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
