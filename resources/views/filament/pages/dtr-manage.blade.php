<x-filament-panels::page>
    @include('filament.pages.partials.compact-management-table-styles')

    <style data-compact-dtr-table>
        .fi-page .fi-wi-table {
            max-width: 100%;
            min-width: 0;
        }

        .fi-page .fi-ta-header {
            gap: .65rem;
            padding: .7rem .85rem !important;
        }

        .fi-page .fi-ta-heading {
            font-size: .85rem;
            line-height: 1.35;
        }

        .fi-page .fi-ta-header-toolbar {
            gap: .45rem;
        }

        .fi-page .fi-ta-header-toolbar .fi-btn {
            min-height: 2rem;
            padding: .35rem .65rem;
            font-size: .75rem;
        }

        .fi-page .fi-ta .fi-badge {
            max-width: 8rem;
            padding: .2rem .42rem;
            font-size: .68rem;
            line-height: 1.1;
            white-space: nowrap;
        }

        .fi-page :is(
            .fi-ta-header-cell-index,
            .fi-ta-cell-index
        ) {
            min-width: 3rem;
            width: 3rem;
        }

        .fi-page :is(
            .fi-ta-header-cell-attendance-status,
            .fi-ta-cell-attendance-status,
            .fi-ta-header-cell-day-part,
            .fi-ta-cell-day-part,
            .fi-ta-header-cell-overtime-status,
            .fi-ta-cell-overtime-status
        ) {
            min-width: 5.75rem;
            width: 5.75rem;
        }

        .fi-page :is(
            .fi-ta-header-cell-date-in,
            .fi-ta-cell-date-in,
            .fi-ta-header-cell-date-out,
            .fi-ta-cell-date-out
        ) {
            min-width: 6.6rem;
            width: 6.6rem;
        }

        .fi-page :is(
            .fi-ta-header-cell-time-in,
            .fi-ta-cell-time-in,
            .fi-ta-header-cell-time-out,
            .fi-ta-cell-time-out,
            .fi-ta-header-cell-schedule-start,
            .fi-ta-cell-schedule-start,
            .fi-ta-header-cell-schedule-end,
            .fi-ta-cell-schedule-end
        ) {
            min-width: 5.5rem;
            width: 5.5rem;
        }

        .fi-page :is(
            .fi-ta-header-cell-late,
            .fi-ta-cell-late,
            .fi-ta-header-cell-undertime,
            .fi-ta-cell-undertime,
            .fi-ta-header-cell-overtime,
            .fi-ta-cell-overtime,
            .fi-ta-header-cell-credited-overtime,
            .fi-ta-cell-credited-overtime,
            .fi-ta-header-cell-work-hrs,
            .fi-ta-cell-work-hrs,
            .fi-ta-header-cell-credited-work-hrs,
            .fi-ta-cell-credited-work-hrs,
            .fi-ta-header-cell-holiday-rate,
            .fi-ta-cell-holiday-rate,
            .fi-ta-header-cell-holiday-excluded,
            .fi-ta-cell-holiday-excluded
        ) {
            min-width: 5.5rem;
            width: 5.5rem;
            text-align: center;
        }

        .fi-page :is(
            .fi-ta-header-cell-schedule-type,
            .fi-ta-cell-schedule-type,
            .fi-ta-header-cell-holiday-type,
            .fi-ta-cell-holiday-type
        ) {
            min-width: 7rem;
            width: 7rem;
        }

        .fi-page .fi-ta-actions {
            gap: .2rem;
            padding-inline: .45rem !important;
        }

        .fi-page .fi-ta-actions .fi-icon-btn {
            height: 1.9rem;
            width: 1.9rem;
        }

        .fi-page .fi-pagination {
            padding: .55rem .75rem !important;
        }

        @media (max-width: 640px) {
            .fi-page .fi-ta-header {
                align-items: stretch;
                padding: .65rem !important;
            }

            .fi-page .fi-ta-header-toolbar,
            .fi-page .fi-ta-header-toolbar > * {
                width: 100%;
            }

            .fi-page .fi-ta-header-toolbar .fi-btn {
                justify-content: center;
            }
        }
    </style>
</x-filament-panels::page>
