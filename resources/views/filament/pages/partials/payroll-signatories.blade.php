<style>
    .signatories {
        display: grid;
        gap: 0.75rem 1.25rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        padding: 0.75rem 1rem;
        border-radius: 0.65rem;
        border: 1px solid rgb(226, 232, 240);
        background: #ffffff;
    }

    .dark .signatories {
        border-color: rgb(51, 65, 85);
        background: rgb(15, 23, 42);
    }

    .signatory-name {
        border-top: 1px solid rgba(148, 163, 184, 0.55);
        color: rgb(15, 23, 42);
        font-size: 0.78rem;
        font-weight: 700;
        padding-top: 0.35rem;
        text-align: center;
    }

    .signatory-label {
        color: rgb(100, 116, 139);
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-top: 0.15rem;
        text-align: center;
    }

    .dark .signatory-name {
        border-top-color: rgb(71, 85, 105);
        color: #f8fafc;
    }

    .dark .signatory-label {
        color: rgb(148, 163, 184);
    }

    @media (max-width: 640px) {
        .signatories {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        .signatories {
            border: 0 !important;
            background: transparent !important;
            padding: 0 !important;
            margin-top: 18px;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 18px;
        }

        .signatory-name,
        .signatory-label {
            color: #000 !important;
        }

        .signatory-name {
            border-top-color: #000 !important;
            font-size: 11px !important;
        }

        .signatory-label {
            font-size: 9px !important;
            text-transform: none !important;
        }
    }
</style>

<div class="signatories">
    <div>
        <div class="signatory-name">{{ $preparedBy ?: 'Prepared By' }}</div>
        <div class="signatory-label">Prepared by</div>
    </div>
    <div>
        <div class="signatory-name">{{ $checkedBy ?: 'Checked By' }}</div>
        <div class="signatory-label">Checked by</div>
    </div>
    <div>
        <div class="signatory-name">{{ $approvedBy ?: 'Approved By' }}</div>
        <div class="signatory-label">Approved by</div>
    </div>
</div>
