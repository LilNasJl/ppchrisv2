<style>
    .signatories {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 18px;
    }

    .signatory-name {
        border-top: 1px solid rgba(71, 85, 105, .65);
        color: rgb(15, 23, 42);
        font-size: 11px;
        font-weight: 700;
        padding-top: 5px;
        text-align: center;
    }

    .signatory-label {
        color: rgb(51, 65, 85);
        font-size: 9px;
        margin-top: 2px;
        text-align: center;
    }

    .dark .signatory-name,
    .dark .signatory-label {
        color: #f8fafc;
    }

    @media print {
        .signatory-name,
        .signatory-label {
            color: #000 !important;
        }

        .signatory-name {
            border-top-color: #000;
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
