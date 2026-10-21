<x-filament-panels::page>
    <style>
        .wfh-workspace { display: grid; gap: 1rem; }
        .wfh-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; padding: 1.3rem 1.5rem; border: 1px solid #3b82f6; border-radius: .8rem; background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50); color: #fff; }
        .wfh-eyebrow { margin: 0 0 .3rem; color: #d9f722; font-size: .72rem; font-weight: 800; letter-spacing: .07em; }
        .wfh-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .wfh-title { margin: 0; font-size: 1.65rem; font-weight: 800; line-height: 1.2; }
        .wfh-subtitle { margin: .4rem 0 0; color: #dbeafe; font-size: .88rem; }
        .wfh-status { display: inline-flex; align-items: center; gap: .5rem; padding: .35rem .7rem; border: 1px solid; border-radius: 999px; font-size: .8rem; font-weight: 800; white-space: nowrap; }
        .wfh-status.is-enabled { background: #065f46; border-color: #34d399; color: #ecfdf5; }
        .wfh-status.is-disabled { background: #7f1d1d; border-color: #f87171; color: #fff1f2; }
        .wfh-status-dot { width: .5rem; height: .5rem; border-radius: 50%; background: currentColor; }
        .wfh-header-actions { display: flex; align-items: center; gap: .6rem; }
        .wfh-card { border: 1px solid #334155; border-radius: .8rem; padding: 1.2rem 1.4rem; background: #111827; color: #f8fafc; }
        .wfh-table-heading { margin: 0 0 .8rem; font-size: 1.05rem; font-weight: 750; }
        @media (max-width: 640px) { .wfh-header, .wfh-card { padding: 1rem; } .wfh-title { font-size: 1.35rem; } }
    </style>

    <div class="wfh-workspace">
        <section class="wfh-card" aria-labelledby="wfh-employees-heading">
            <h2 class="wfh-table-heading" id="wfh-employees-heading">Employees assigned on-site Saturdays</h2>
            {{ $this->content }}
        </section>
    </div>
</x-filament-panels::page>
