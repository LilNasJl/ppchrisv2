@php
    $logoSrc = \App\Support\CompanyExportHeader::logoDataUri() ?? asset('image/ppcblueblack.png');
@endphp

<style>
    .company-export-header {
        align-items: start;
        color: #000;
        display: none;
        gap: 10px;
        grid-template-columns: 82px 1fr 82px;
        margin-bottom: 8px;
        text-align: center;
    }

    .company-export-spacer {
        width: 82px;
    }

    .company-export-logo {
        height: 58px;
        object-fit: contain;
        width: 82px;
    }

    .company-export-title {
        font-size: 17px;
        font-weight: 800;
        line-height: 1.15;
    }

    .company-export-address {
        font-size: 11px;
        line-height: 1.25;
    }

    @media print {
        .company-export-header {
            display: grid;
            break-inside: avoid;
            color: #000 !important;
        }
    }
</style>

<div class="company-export-header">
    <img src="{{ $logoSrc }}" alt="Philfumes logo" class="company-export-logo">

    <div>
        <div class="company-export-title">{{ \App\Support\CompanyExportHeader::COMPANY_NAME }}</div>
        <div class="company-export-address">{{ \App\Support\CompanyExportHeader::ADDRESS_LINE }}</div>
        <div class="company-export-address">{{ \App\Support\CompanyExportHeader::PROVINCE_LINE }}</div>
    </div>

    <div class="company-export-spacer"></div>
</div>
