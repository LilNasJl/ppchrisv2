<x-filament-panels::page>
    @php
        $periods = $this->openPeriods();
        $branches = $this->branches();
        $counts = $this->previewCounts();
        $total = $counts['official'] + $counts['visible'];
        $selectedPeriod = $periods->firstWhere('id', (int) $this->periodId);
        $selectedBranch = $branches->firstWhere('id', (int) $this->branchId);
    @endphp

    <style>
        .dtr-clear-page { display: grid; gap: 1rem; }
        .dtr-clear-header {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
            gap: 1rem; padding: 1.25rem 1.5rem; border: 1px solid #3b82f6;
            border-radius: .75rem; color: #fff;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
        }
        .dtr-clear-header p { margin: 0; }
        .dtr-clear-eyebrow { color: #d9f722; font-size: .7rem; font-weight: 800; }
        .dtr-clear-header h1 { margin: .25rem 0; color: #fff; font-size: 1.65rem; font-weight: 800; line-height: 1.2; }
        .dtr-clear-subtitle { color: #e0ecff; font-size: .9rem; }
        .dtr-clear-return {
            display: inline-flex; align-items: center; gap: .5rem; min-height: 2.5rem;
            padding: .5rem .85rem; border: 1px solid #8bb9f5; border-radius: .4rem;
            color: #fff; background: #ffffff1a; font-size: .85rem; font-weight: 700;
        }
        .dtr-clear-return:hover { background: #ffffff30; }
        .dtr-clear-return svg { width: 1rem; height: 1rem; }
        .dtr-clear-panel { border: 1px solid #303030; border-radius: .5rem; background: #000000; overflow: hidden; }
        .dtr-clear-panel-heading { padding: .9rem 1.2rem; border-bottom: 1px solid #27272a; background: #000000; }
        .dtr-clear-panel-heading h2 { margin: 0; color: #f8fafc; font-size: 1rem; font-weight: 700; }
        .dtr-clear-form { display: grid; gap: 1rem; padding: 1.2rem; }
        .dtr-clear-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .dtr-clear-field { display: grid; gap: .4rem; min-width: 0; }
        .dtr-clear-field label { color: #e2e8f0; font-size: .8rem; font-weight: 700; }
        .dtr-clear-field select, .dtr-clear-field input {
            width: 100%; min-height: 2.7rem; padding: .55rem .7rem; border: 1px solid #3f3f46;
            border-radius: .4rem; outline: none; color: #f8fafc; background: #000000;
        }
        .dtr-clear-field select:focus, .dtr-clear-field input:focus { border-color: #60a5fa; box-shadow: 0 0 0 2px #3b82f633; }
        .dtr-clear-field select option { color: #f8fafc; background: #000000; }
        .dtr-clear-error { color: #fda4af; font-size: .75rem; }
        .dtr-clear-preview { display: flex; gap: 1.5rem; flex-wrap: wrap; padding: .85rem 1rem; border: 1px solid #303030; border-radius: .4rem; background: #000000; }
        .dtr-clear-stat { display: grid; gap: .15rem; }
        .dtr-clear-stat strong { color: #f8fafc; font-size: 1.15rem; font-variant-numeric: tabular-nums; }
        .dtr-clear-stat span { color: #94a3b8; font-size: .75rem; }
        .dtr-clear-warning { margin: 0; color: #fbbf24; font-size: .82rem; line-height: 1.5; }
        .dtr-clear-security { display: grid; grid-template-columns: 9rem repeat(2, minmax(0, 1fr)); gap: 1rem; align-items: end; max-width: 780px; }
        .dtr-clear-code-group { display: grid; gap: .4rem; }
        .dtr-clear-code-group span { color: #e2e8f0; font-size: .8rem; font-weight: 700; }
        .dtr-clear-code {
            display: flex; align-items: center; justify-content: center; min-height: 2.7rem;
            padding: .55rem .7rem; border: 1px solid #3f3f46; border-radius: .4rem;
            color: #f8fafc; background: #000000; font-family: ui-monospace, monospace;
            font-size: 1rem; font-weight: 800; user-select: all;
        }
        .dtr-clear-button {
            display: inline-flex; align-items: center; justify-content: center; gap: .45rem;
            min-height: 2.6rem; padding: .55rem .9rem; border: 1px solid #ef4444;
            border-radius: .4rem; color: #fff; background: #b91c1c; font-size: .85rem; font-weight: 700;
        }
        .dtr-clear-button:hover:not(:disabled) { background: #dc2626; }
        .dtr-clear-button:disabled { opacity: .45; cursor: not-allowed; }
        .dtr-clear-button svg { width: 1rem; height: 1rem; }
        @media (max-width: 640px) {
            .dtr-clear-header { padding: 1rem; }
            .dtr-clear-grid { grid-template-columns: 1fr; }
            .dtr-clear-security { grid-template-columns: 1fr; }
            .dtr-clear-form { padding: 1rem; }
            .dtr-clear-button { width: 100%; }
        }
    </style>

    <div class="dtr-clear-page">
        <header class="dtr-clear-header">
            <div>
                <p class="dtr-clear-eyebrow">WORKFORCE RECORDS</p>
                <h1>Clear DTR Entries</h1>
                <p class="dtr-clear-subtitle">Remove DTR entries for one branch and an open payroll period.</p>
            </div>
            <a href="{{ \App\Filament\Pages\Dtr::getUrl() }}" class="dtr-clear-return">
                <x-filament::icon icon="heroicon-o-arrow-left" />
                <span>Return to DTR</span>
            </a>
        </header>

        <section class="dtr-clear-panel" aria-labelledby="dtr-clear-selection-title">
            <div class="dtr-clear-panel-heading"><h2 id="dtr-clear-selection-title">Select records to clear</h2></div>
            <form wire:submit="clearEntries" class="dtr-clear-form">
                <div class="dtr-clear-grid">
                    <div class="dtr-clear-field">
                        <label for="dtr-clear-period">Payroll period</label>
                        <select id="dtr-clear-period" wire:model.live="periodId" @disabled($periods->isEmpty())>
                            <option value="">Select an open period</option>
                            @foreach($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->title }} ({{ $period->date_start?->format('M d') }} - {{ $period->date_end?->format('M d, Y') }})</option>
                            @endforeach
                        </select>
                        @error('periodId') <span class="dtr-clear-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="dtr-clear-field">
                        <label for="dtr-clear-branch">Branch / station</label>
                        <select id="dtr-clear-branch" wire:model.live="branchId" @disabled($branches->isEmpty())>
                            <option value="">Select a branch or station</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                            @endforeach
                        </select>
                        @error('branchId') <span class="dtr-clear-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($periods->isEmpty())
                    <p class="dtr-clear-warning">There are no open payroll periods available.</p>
                @endif

                @if($selectedPeriod && $selectedBranch)
                    <div class="dtr-clear-preview" role="status" aria-live="polite">
                        <div class="dtr-clear-stat"><strong>{{ number_format($counts['official']) }}</strong><span>HR DTR entries</span></div>
                        <div class="dtr-clear-stat"><strong>{{ number_format($counts['visible']) }}</strong><span>Employee-visible entries</span></div>
                    </div>
                    <p class="dtr-clear-warning">This clears all entries in the selected scope, including generated On Field DTR entries. Request history remains. A later import or device sync may recreate entries.</p>
                    <div class="dtr-clear-security">
                        <div class="dtr-clear-code-group">
                            <span>Confirmation code</span>
                            <output class="dtr-clear-code" aria-label="Confirmation code">{{ $this->challenge }}</output>
                        </div>
                        <div class="dtr-clear-field">
                            <label for="dtr-clear-confirmation">Type the code</label>
                            <input id="dtr-clear-confirmation" type="text" wire:model="confirmation" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="8" required>
                            @error('confirmation') <span class="dtr-clear-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="dtr-clear-field">
                            <label for="dtr-clear-password">Current password</label>
                            <input id="dtr-clear-password" type="password" wire:model="currentPassword" autocomplete="current-password" required>
                            @error('currentPassword') <span class="dtr-clear-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="dtr-clear-button" @disabled($total === 0) wire:loading.attr="disabled" wire:target="clearEntries">
                            <x-filament::icon icon="heroicon-o-trash" />
                            <span>Clear {{ number_format($total) }} DTR {{ $total === 1 ? 'entry' : 'entries' }}</span>
                        </button>
                    </div>
                @endif
            </form>
        </section>
    </div>
</x-filament-panels::page>
