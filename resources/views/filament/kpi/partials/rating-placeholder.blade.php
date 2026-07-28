<style>
    .kpi-rating-placeholder {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 18px;
    }

    .kpi-rating-placeholder strong {
        color: #172554;
        display: block;
    }

    .kpi-rating-placeholder p {
        color: #1e40af;
        font-size: 13px;
        line-height: 1.55;
        margin: 7px 0 0;
    }

    .dark .kpi-rating-placeholder {
        background: rgba(30, 64, 175, .18);
        border-color: rgba(30, 64, 175, .72);
    }

    .dark .kpi-rating-placeholder strong,
    .dark .kpi-rating-placeholder p {
        color: #dbeafe;
    }
</style>

<div class="kpi-rating-placeholder">
    <strong>{{ $target->target_name }}</strong>
    <p>
        The rating target is connected and ready. KPI criteria, weights, and scoring fields will be added through KPI Configuration.
    </p>
</div>
