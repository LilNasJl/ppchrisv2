<style>
    .fi-page-header-main-ctn > header.fi-header {
        align-items: center;
        background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
        border: 1px solid #3b82f6;
        border-radius: .75rem;
        color: #fff;
        gap: 1rem 1.5rem;
        padding: 1.1rem 1.4rem;
        width: 100%;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-heading {
        color: #fff !important;
        font-size: 1.55rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
        color: #d9f722;
        content: "EMPLOYEE SELF SERVICE";
        display: block;
        font-size: .65rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: .25rem;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-subheading {
        color: #e0ecff !important;
        font-size: .875rem;
        margin-top: .3rem;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-breadcrumbs {
        display: none !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn {
        margin-left: auto;
        margin-top: 0;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn {
        background: rgba(10, 31, 68, .55);
        border-color: rgba(147, 197, 253, .4);
        color: #fff;
        min-height: 2.35rem;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn:hover {
        background: rgba(10, 31, 68, .85);
        border-color: rgba(191, 219, 254, .7);
        color: #fff;
    }

    @media (max-width: 640px) {
        .fi-page-header-main-ctn > header.fi-header {
            align-items: stretch;
            padding: 1rem;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn {
            justify-content: flex-start;
            margin-left: 0;
            width: 100%;
        }
    }
</style>
