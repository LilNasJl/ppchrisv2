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

        .dtr-viewer-panel-header {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .dtr-viewer-title {
            color: rgb(15, 23, 42);
            font-size: 15px;
            font-weight: 800;
        }

        .dark .dtr-viewer-title {
            color: #f8fafc;
        }

        .dtr-viewer-subtitle {
            color: #64748b;
            font-size: 12px;
            margin-top: 2px;
        }

        .dark .dtr-viewer-subtitle {
            color: #94a3b8;
        }

        .dtr-viewer-form {
            max-width: 760px;
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
            .dtr-viewer-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .dtr-viewer-frame {
                min-height: 680px;
            }
        }
    </style>

    <div class="dtr-viewer-shell">
        <section class="dtr-viewer-panel">
            <div class="dtr-viewer-panel-header">
                <div>
                    <div class="dtr-viewer-title">Manage D.T.R</div>
                    <div class="dtr-viewer-subtitle">Payroll period and branch</div>
                </div>
            </div>

            <div class="dtr-viewer-form">
                {{ $this->form }}
            </div>
        </section>

        <section class="dtr-viewer-panel dtr-viewer-frame-wrap">
            <iframe
                class="dtr-viewer-frame"
                src="{{ asset('page/hr_atttendance_viewer.html') }}?period_id={{ $this->period_id }}&branch_id={{ $this->branch_id }}"
                title="D.T.R Viewer">
            </iframe>
        </section>
    </div>

</x-filament-panels::page>
