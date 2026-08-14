<x-filament-panels::page>
    <style>
        .dtr-viewer-shell {
            display: grid;
            gap: 16px;
        }

        .dtr-viewer-panel {
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 8px;
            padding: 16px;
        }

        .dark .dtr-viewer-panel {
            background: rgba(17, 24, 39, .55);
            border-color: rgba(148, 163, 184, .18);
        }

        .dtr-viewer-frame-wrap {
            overflow: hidden;
            padding: 0;
        }

        .dtr-viewer-frame {
            border: 0;
            display: block;
            min-height: 760px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .dtr-viewer-frame {
                min-height: 680px;
            }
        }
    </style>

    <div class="dtr-viewer-shell">
        @php
            $viewerQuery = http_build_query([
                'endpoint' => route('hr_tools.import.dtr'),
            ]);
            $payrollPeriodOptions = base64_encode((string) json_encode($this->getPayrollPeriodOptions(), JSON_UNESCAPED_UNICODE));
            $branchOptions = base64_encode((string) json_encode($this->getBranchOptions(), JSON_UNESCAPED_UNICODE));
        @endphp

        <section class="dtr-viewer-panel dtr-viewer-frame-wrap">
            <iframe
                class="dtr-viewer-frame"
                data-period-options="{{ $payrollPeriodOptions }}"
                data-branch-options="{{ $branchOptions }}"
                src="{{ asset('page/hr_atttendance_viewer.html') }}?{{ $viewerQuery }}"
                wire:key="dtr-viewer"
                title="D.T.R Viewer">
            </iframe>
        </section>
    </div>

</x-filament-panels::page>
