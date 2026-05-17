<style>
    .export-generated-at {
        color: #000;
        display: none;
        font-size: 10px;
        margin-top: 10px;
        text-align: right;
    }

    @media print {
        .export-generated-at {
            color: #000 !important;
            display: block;
        }
    }
</style>

<div class="export-generated-at">
    Date Generated: {{ \App\Support\CompanyExportHeader::generatedAt() }}
</div>
