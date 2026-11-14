<style>
    .fi-page-header-main-ctn > header.fi-header {
        align-items: center;
        background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
        border: 1px solid #3b82f6;
        border-radius: .75rem;
        color: #fff;
        gap: 1rem 1.5rem;
        padding: 0.95rem 1.25rem;
        width: 100%;
        box-sizing: border-box;
    }

    .dark .fi-page-header-main-ctn > header.fi-header {
        border-color: #1e293b !important;
        background: linear-gradient(110deg, #0f274a, #08152b 68%, #030814) !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-heading {
        color: #fff !important;
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.015em;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-heading::before {
        color: #d9f722;
        content: "EMPLOYEE LEAVE MANAGEMENT";
        display: block;
        font-size: .65rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: .25rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-subheading {
        display: none !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-breadcrumbs {
        display: none !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn {
        margin-left: auto;
        margin-top: 0;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 0.5rem !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-ac {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        justify-content: flex-end !important;
        gap: 0.5rem !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 2.2rem !important;
        padding: 0.35rem 0.85rem !important;
        border-radius: 0.45rem !important;
        font-size: 0.79rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em !important;
        white-space: nowrap !important;
        transition: all 120ms ease !important;
        box-sizing: border-box !important;
        gap: 0.4rem !important;
    }

    /* Style primary action (New Leave Request) */
    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn[wire\:click*="newLeaveRequest"],
    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-ac > :first-child .fi-btn {
        background-color: #2563eb !important;
        border: 1px solid #3b82f6 !important;
        color: #ffffff !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1) !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn[wire\:click*="newLeaveRequest"]:hover,
    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-ac > :first-child .fi-btn:hover {
        background-color: #1d4ed8 !important;
        border-color: #2563eb !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35) !important;
    }

    /* Style secondary action (Leave Credits) */
    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn[wire\:click*="leaveCredits"],
    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-ac > :last-child:not(:first-child) .fi-btn {
        background: rgba(15, 23, 42, 0.6) !important;
        border: 1px solid rgba(148, 163, 184, 0.35) !important;
        color: #e2e8f0 !important;
    }

    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn[wire\:click*="leaveCredits"]:hover,
    .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-ac > :last-child:not(:first-child) .fi-btn:hover {
        background: rgba(15, 23, 42, 0.9) !important;
        border-color: rgba(203, 213, 225, 0.6) !important;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    @media (max-width: 640px) {
        .fi-page-header-main-ctn > header.fi-header {
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            padding: 0.85rem 0.95rem !important;
            gap: 0.75rem !important;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-heading {
            font-size: 1.25rem !important;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn {
            display: block !important;
            width: 100% !important;
            margin-left: 0 !important;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-ac {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            width: 100% !important;
            gap: 0.5rem !important;
        }

        .fi-page-header-main-ctn > header.fi-header .fi-header-actions-ctn .fi-btn {
            width: 100% !important;
            min-height: 2.35rem !important;
            padding: 0.4rem 0.5rem !important;
            font-size: 0.775rem !important;
            border-radius: 0.5rem !important;
        }
    }
</style>
