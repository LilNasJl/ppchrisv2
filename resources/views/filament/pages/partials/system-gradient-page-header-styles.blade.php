<style>
    .fi-page-header-main-ctn > header.fi-header {
        align-items: center;
        gap: 1rem 1.5rem;
        width: 100%;
        padding: 1.25rem 1.5rem;
        border: 1px solid #3b82f6;
        border-radius: 0.75rem;
        background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
        color: #fff;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-heading {
        color: #fff !important;
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: 0;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
        display: block;
        margin-bottom: 0.25rem;
        color: #d9f722;
        content: "ANALYTICS & REPORTS";
        font-size: 0.7rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-subheading {
        max-width: 52rem;
        margin-top: 0.35rem;
        color: #e0ecff !important;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-breadcrumbs,
    .fi-page-header-main-ctn > header.fi-header .fi-breadcrumbs a,
    .fi-page-header-main-ctn > header.fi-header .fi-breadcrumbs svg {
        color: #bfdbfe;
    }

    .fi-page-header-main-ctn > header.fi-header.fi-header-has-breadcrumbs .fi-header-actions-ctn {
        margin-top: 0;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn {
        margin-left: auto;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn {
        min-height: 2.35rem;
        border-color: rgb(147 197 253 / 0.4);
        background: rgb(10 31 68 / 0.55);
        color: #fff;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn:hover {
        border-color: rgb(191 219 254 / 0.65);
        background: rgb(10 31 68 / 0.85);
        color: #fff;
    }

    @media (max-width: 640px) {
        .fi-page-header-main-ctn > header.fi-header {
            align-items: stretch;
            padding: 1rem;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn {
            width: 100%;
            margin-left: 0;
            justify-content: flex-start;
        }
    }
</style>
