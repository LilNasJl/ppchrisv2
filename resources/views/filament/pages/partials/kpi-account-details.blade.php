<style>
    .kpi-account-details {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .kpi-account-detail {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 16px;
    }

    .kpi-account-detail.is-wide {
        background: #fff;
        grid-column: 1 / -1;
    }

    .kpi-account-detail span {
        color: #1d4ed8;
        display: block;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .kpi-account-detail strong,
    .kpi-account-detail p {
        color: #0f172a;
        display: block;
        line-height: 1.55;
        margin: 5px 0 0;
    }

    .dark .kpi-account-detail {
        background: rgba(30, 64, 175, .18);
        border-color: rgba(30, 64, 175, .72);
    }

    .dark .kpi-account-detail.is-wide {
        background: #111827;
    }

    .dark .kpi-account-detail strong,
    .dark .kpi-account-detail p {
        color: #f8fafc;
    }

    @media (max-width: 520px) {
        .kpi-account-details {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="kpi-account-details">
    <section class="kpi-account-detail">
        <span>Username</span>
        <strong>{{ $account->username }}</strong>
    </section>

    <section class="kpi-account-detail">
        <span>Portal Access</span>
        <strong>{{ $account->is_active ? 'Enabled' : 'Disabled' }}</strong>
    </section>

    <section class="kpi-account-detail is-wide">
        <span>{{ $account->scope_label }} Coverage</span>
        <p>{{ $account->scope_summary }}</p>
    </section>
</div>
