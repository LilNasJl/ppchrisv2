<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .hr-ofd-workspace {
            display: grid;
            width: 100%;
            gap: 0.75rem;
        }

        .hr-ofd-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.1rem 1.35rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
        }

        .hr-ofd-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .hr-ofd-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .hr-ofd-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .hr-ofd-subtitle {
            margin: 0;
            color: #e0ecff;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .hr-ofd-header-actions {
            flex: 1 1 420px;
            min-width: 0;
        }

        .hr-ofd-header-actions > div {
            justify-content: flex-end;
        }

        @media (max-width: 760px) {
            .hr-ofd-header {
                align-items: stretch;
                padding: 1rem;
            }

            .hr-ofd-title {
                font-size: 1.3rem;
            }

            .hr-ofd-header-actions {
                flex-basis: 100%;
            }

            .hr-ofd-header-actions > div {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="hr-ofd-workspace">
        <header class="hr-ofd-header">
            <div class="hr-ofd-header-copy">
                <p class="hr-ofd-eyebrow">WORKFORCE RECORDS</p>
                <h1 class="hr-ofd-title">On Field DTR</h1>
                <p class="hr-ofd-subtitle">Review submitted attendance details, supporting proof, and HR decisions.</p>
            </div>

            <div class="hr-ofd-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

    @include('filament.pages.partials.on-field-dtr-details', [
        'submission' => $this->submission,
        'showReviewer' => true,
        'compact' => true,
    ])
    </div>
</x-filament-panels::page>
