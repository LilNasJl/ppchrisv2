<x-filament-panels::page>
    <style>
        .fi-page-header-main-ctn > header.fi-header {
            display: none !important;
        }

        .db-mgmt-container {
            display: grid;
            gap: 1.25rem;
            width: 100%;
        }

        .db-mgmt-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem 1.5rem;
            padding: 1.25rem 1.5rem;
            border: 1px solid #3b82f6;
            border-radius: 0.75rem;
            background: linear-gradient(110deg, #1257b8, #173c70 68%, #102a50);
            color: #ffffff;
            box-shadow: 0 4px 14px -2px rgba(18, 87, 184, 0.25);
        }

        .db-mgmt-header-copy {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 0.25rem;
        }

        .db-mgmt-eyebrow {
            margin: 0;
            color: #d9f722;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .db-mgmt-title {
            margin: 0;
            color: #ffffff;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .db-mgmt-subtitle {
            margin: 0;
            color: #bfdbfe;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .db-mgmt-header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .db-mgmt-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .db-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .dark .db-card {
            background: rgb(24, 24, 27);
            border-color: rgba(63, 63, 70, 0.6);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }

        .db-card:hover {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.12);
        }

        .db-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .db-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.625rem;
            flex-shrink: 0;
        }

        .db-card-icon svg {
            width: 1.5rem;
            height: 1.5rem;
        }

        .db-card-icon.blue {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        .dark .db-card-icon.blue {
            background: rgba(37, 99, 235, 0.25);
            color: #60a5fa;
        }

        .db-card-icon.emerald {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }

        .dark .db-card-icon.emerald {
            background: rgba(16, 185, 129, 0.25);
            color: #34d399;
        }

        .db-card-title-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .db-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: rgb(15, 23, 42);
            margin: 0;
        }

        .dark .db-card-title {
            color: rgb(244, 244, 245);
        }

        .db-card-desc {
            font-size: 0.85rem;
            color: rgb(100, 116, 139);
            margin: 0;
            line-height: 1.45;
        }

        .dark .db-card-desc {
            color: rgb(161, 161, 170);
        }

        .db-card-features {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 0.875rem;
            background: rgb(248, 250, 252);
            border-radius: 0.5rem;
            border: 1px solid rgb(241, 245, 249);
        }

        .dark .db-card-features {
            background: rgba(39, 39, 42, 0.5);
            border-color: rgba(63, 63, 70, 0.4);
        }

        .db-feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: rgb(71, 85, 105);
        }

        .dark .db-feature-item {
            color: rgb(212, 212, 216);
        }

        .db-feature-item svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            color: #10b981;
        }

        .db-card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(226, 232, 240, 0.8);
        }

        .dark .db-card-actions {
            border-top-color: rgba(63, 63, 70, 0.6);
        }

        @media (max-width: 840px) {
            .db-mgmt-grid {
                grid-template-columns: minmax(0, 1fr);
            }

            .db-card-actions > * {
                flex: 1 1 100%;
            }
        }
    </style>

    <div class="db-mgmt-container">
        <!-- Gradient Top Header -->
        <header class="db-mgmt-header">
            <div class="db-mgmt-copy">
                <p class="db-mgmt-eyebrow">SETTINGS</p>
                <h1 class="db-mgmt-title">Database Management</h1>
                <p class="db-mgmt-subtitle">Manage snapshots, exports, and system backup archives securely.</p>
            </div>

            <div class="db-mgmt-header-actions">
                <x-filament::actions :actions="$this->getCachedHeaderActions()" />
            </div>
        </header>

        <!-- Main Content Cards -->
        <div class="db-mgmt-grid">
            <!-- Database Backup Card -->
            <div class="db-card">
                <div>
                    <div class="db-card-header">
                        <div class="db-card-icon blue">
                            <x-heroicon-o-circle-stack />
                        </div>
                        <div class="db-card-title-group">
                            <h2 class="db-card-title">Database SQL Backup</h2>
                            <p class="db-card-desc">Exports SQL dumps of all current tables, schemas, and relational records.</p>
                        </div>
                    </div>

                    <div class="db-card-features">
                        <div class="db-feature-item">
                            <x-heroicon-s-check-circle />
                            <span>Includes complete table schemas and row contents</span>
                        </div>
                        <div class="db-feature-item">
                            <x-heroicon-s-check-circle />
                            <span>Available in raw SQL format (.sql) or gzip compression (.sql.gz)</span>
                        </div>
                    </div>
                </div>

                <div class="db-card-actions">
                    <x-filament::button
                        tag="a"
                        href="{{ $sqlDownloadUrl }}"
                        target="_blank"
                        download
                        data-navigate-ignore="true"
                        color="primary"
                        icon="heroicon-o-document-text"
                    >
                        Download SQL
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ $sqlGzDownloadUrl }}"
                        target="_blank"
                        download
                        data-navigate-ignore="true"
                        color="gray"
                        icon="heroicon-o-archive-box"
                    >
                        Download Compressed (.gz)
                    </x-filament::button>
                </div>
            </div>

            <!-- Full System Backup Card -->
            <div class="db-card">
                <div>
                    <div class="db-card-header">
                        <div class="db-card-icon emerald">
                            <x-heroicon-o-server-stack />
                        </div>
                        <div class="db-card-title-group">
                            <h2 class="db-card-title">Full System Backup</h2>
                            <p class="db-card-desc">Comprehensive archive containing both database dumps and uploaded media assets.</p>
                        </div>
                    </div>

                    <div class="db-card-features">
                        <div class="db-feature-item">
                            <x-heroicon-s-check-circle />
                            <span>Full database snapshot bundled with application uploads</span>
                        </div>
                        <div class="db-feature-item">
                            <x-heroicon-s-check-circle />
                            <span>ZIP archive compatible with Full System Restore</span>
                        </div>
                    </div>
                </div>

                <div class="db-card-actions">
                    <x-filament::button
                        tag="a"
                        href="{{ $fullDownloadUrl }}"
                        target="_blank"
                        download
                        data-navigate-ignore="true"
                        color="success"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Download Full Backup (.zip)
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
