<x-filament-panels::page>
    <style>
        .station-dtr-import-shell {
            display: block;
            width: 100%;
            height: calc(100dvh - 10rem);
            min-height: 720px;
            overflow: hidden;
            border: 1px solid rgb(229, 231, 235);
            border-radius: 0.5rem;
            background: rgb(255, 255, 255);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
        }

        .dark .station-dtr-import-shell {
            border-color: rgb(31, 41, 55);
            background: rgb(17, 24, 39);
        }

        .station-dtr-import-frame {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 720px;
            border: 0;
        }

        @media (max-width: 768px) {
            .station-dtr-import-shell,
            .station-dtr-import-frame {
                min-height: 680px;
            }
        }
    </style>

    @php
        $payrollPeriodOptions = base64_encode((string) json_encode($this->getPayrollPeriodOptions(), JSON_UNESCAPED_UNICODE));
        $branchOptions = base64_encode((string) json_encode($this->getBranchOptions(), JSON_UNESCAPED_UNICODE));
    @endphp

    <div class="station-dtr-import-shell">
        <iframe
            src="{{ $this->getIframeUrl() }}"
            class="station-dtr-import-frame"
            data-period-options="{{ $payrollPeriodOptions }}"
            data-branch-options="{{ $branchOptions }}"
            loading="eager"
            title="Import Station D.T.R"
        ></iframe>
    </div>
</x-filament-panels::page>
