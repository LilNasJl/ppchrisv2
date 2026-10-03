<x-filament-panels::page>
    <div class="db-mgmt-container">
        <!-- Overview Hero / Banner Section -->
        <x-filament::section class="db-mgmt-hero-section">
            <div class="db-mgmt-hero-grid">
                <div class="db-mgmt-hero-main">
                    <div class="db-mgmt-badge-row">
                        <span class="db-mgmt-badge">
                            <x-filament::icon icon="heroicon-m-circle-stack" class="h-4 w-4" />
                            <span>System & Database Operations</span>
                        </span>
                    </div>

                    <h2 class="db-mgmt-hero-title">Protect, Backup & Recover HRIS Data</h2>
                    <p class="db-mgmt-hero-description">
                        Generate full database snapshots and asset bundles for backup or migration. Backups created here are structured for seamless 1-click import into <strong>phpMyAdmin</strong>, <strong>MySQL CLI</strong>, or through the built-in restore utility.
                    </p>
                </div>

                <!-- Database Metrics Card -->
                <div class="db-mgmt-stats-card">
                    <div class="db-mgmt-stats-header">
                        <x-filament::icon icon="heroicon-m-server-stack" class="h-4 w-4 text-primary-500" />
                        <span>Live Database Specs</span>
                    </div>
                    <div class="db-mgmt-stats-grid">
                        <div class="db-mgmt-stat-item">
                            <span class="db-mgmt-stat-label">Database</span>
                            <span class="db-mgmt-stat-val db-mgmt-truncate" title="{{ $databaseName }}">{{ $databaseName }}</span>
                        </div>
                        <div class="db-mgmt-stat-item">
                            <span class="db-mgmt-stat-label">Tables</span>
                            <span class="db-mgmt-stat-val">{{ $tablesCount }} tables</span>
                        </div>
                        <div class="db-mgmt-stat-item">
                            <span class="db-mgmt-stat-label">Storage Size</span>
                            <span class="db-mgmt-stat-val">{{ $databaseSizeMb }} MB</span>
                        </div>
                        <div class="db-mgmt-stat-item">
                            <span class="db-mgmt-stat-label">Engine</span>
                            <span class="db-mgmt-stat-val">MySQL / InnoDB</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <!-- Main Backup Action Cards -->
        <div class="db-mgmt-cards-grid">
            <!-- Card 1: Database SQL Backup -->
            <x-filament::section class="db-mgmt-action-card">
                <x-slot name="heading">
                    <div class="db-mgmt-card-title-row">
                        <div class="db-mgmt-card-icon-wrap primary">
                            <x-filament::icon icon="heroicon-m-circle-stack" class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="db-mgmt-card-title">Database Backup</h3>
                            <p class="db-mgmt-card-subtitle">Complete schema, constraints & table rows</p>
                        </div>
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::badge color="info">
                        phpMyAdmin Ready
                    </x-filament::badge>
                </x-slot>

                <div class="db-mgmt-card-body">
                    <p class="db-mgmt-card-text">
                        Includes all <strong>{{ $tablesCount }} tables</strong>, foreign keys, UTF-8 collation, and transactional bulk insertion syntax. Fully optimized for instant upload into phpMyAdmin.
                    </p>

                    <div class="db-mgmt-btn-group">
                        <x-filament::button
                            tag="a"
                            href="{{ $sqlGzDownloadUrl }}"
                            target="_blank"
                            download
                            data-navigate-ignore="true"
                            color="primary"
                            icon="heroicon-m-archive-box"
                            class="db-mgmt-primary-btn"
                        >
                            Download .sql.gz (Fast)
                        </x-filament::button>

                        <x-filament::button
                            tag="a"
                            href="{{ $sqlDownloadUrl }}"
                            target="_blank"
                            download
                            data-navigate-ignore="true"
                            color="gray"
                            icon="heroicon-m-document-text"
                        >
                            Raw .sql (Plain)
                        </x-filament::button>
                    </div>

                    <div class="db-mgmt-note-box info">
                        <x-filament::icon icon="heroicon-m-sparkles" class="h-4 w-4 shrink-0" />
                        <span><strong>Recommended:</strong> <code class="db-mgmt-code">.sql.gz</code> is ~90% smaller (1.7 MB vs 16 MB). It bypasses Hostinger file upload limits and imports automatically in phpMyAdmin.</span>
                    </div>
                </div>
            </x-filament::section>

            <!-- Card 2: Full System Archive -->
            <x-filament::section class="db-mgmt-action-card">
                <x-slot name="heading">
                    <div class="db-mgmt-card-title-row">
                        <div class="db-mgmt-card-icon-wrap success">
                            <x-filament::icon icon="heroicon-m-folder-arrow-down" class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="db-mgmt-card-title">Full System Archive</h3>
                            <p class="db-mgmt-card-subtitle">Database + Employee uploaded media & files</p>
                        </div>
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::badge color="success">
                        Complete ZIP
                    </x-filament::badge>
                </x-slot>

                <div class="db-mgmt-card-body">
                    <p class="db-mgmt-card-text">
                        Bundles the MySQL database SQL file together with employee profile photos, office memos, leave proof documents, and support tickets into a single compressed ZIP archive.
                    </p>

                    <div class="db-mgmt-btn-group">
                        <x-filament::button
                            tag="a"
                            href="{{ $fullDownloadUrl }}"
                            target="_blank"
                            download
                            data-navigate-ignore="true"
                            color="success"
                            icon="heroicon-m-arrow-down-tray"
                            class="db-mgmt-success-btn"
                        >
                            Download Full Backup ZIP
                        </x-filament::button>
                    </div>

                    <div class="db-mgmt-note-box success">
                        <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4 shrink-0" />
                        <span>Use this for complete server transfers, disaster recovery, or offline archiving. Can be restored using the top-right button.</span>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- phpMyAdmin Import Guide Section -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="db-mgmt-guide-header">
                    <x-filament::icon icon="heroicon-m-lifebuoy" class="h-5 w-5 text-primary-500" />
                    <span>How to Import into phpMyAdmin (Local Server or Hostinger)</span>
                </div>
            </x-slot>

            <div class="db-mgmt-steps-grid">
                <div class="db-mgmt-step-card">
                    <div class="db-mgmt-step-num">1</div>
                    <h4 class="db-mgmt-step-title">Open phpMyAdmin</h4>
                    <p class="db-mgmt-step-desc">Log into phpMyAdmin from your Hostinger hPanel or local development dashboard.</p>
                </div>

                <div class="db-mgmt-step-card">
                    <div class="db-mgmt-step-num">2</div>
                    <h4 class="db-mgmt-step-title">Select Database</h4>
                    <p class="db-mgmt-step-desc">Click your database (<code class="db-mgmt-code">{{ $databaseName }}</code>) in the left navigation sidebar.</p>
                </div>

                <div class="db-mgmt-step-card">
                    <div class="db-mgmt-step-num">3</div>
                    <h4 class="db-mgmt-step-title">Click "Import"</h4>
                    <p class="db-mgmt-step-desc">Navigate to the top menu tabs and click on the <strong>Import</strong> tab.</p>
                </div>

                <div class="db-mgmt-step-card">
                    <div class="db-mgmt-step-num">4</div>
                    <h4 class="db-mgmt-step-title">Upload & Execute</h4>
                    <p class="db-mgmt-step-desc">Choose your downloaded <code class="db-mgmt-code">.sql.gz</code> file and click <strong>Go / Import</strong>.</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    <style>
        .db-mgmt-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .db-mgmt-hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            align-items: center;
        }

        @media (min-width: 1024px) {
            .db-mgmt-hero-grid {
                grid-template-columns: 1.7fr 1.1fr;
            }
        }

        .db-mgmt-badge-row {
            margin-bottom: 0.65rem;
        }

        .db-mgmt-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.725rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .dark .db-mgmt-badge {
            color: #93c5fd;
            background: rgba(30, 58, 138, 0.35);
            border-color: rgba(96, 165, 250, 0.25);
        }

        .db-mgmt-hero-title {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.3;
            color: #0f172a;
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.015em;
        }

        .dark .db-mgmt-hero-title {
            color: #f8fafc;
        }

        .db-mgmt-hero-description {
            font-size: 0.875rem;
            line-height: 1.6;
            color: #64748b;
            margin: 0;
            max-width: 65ch;
        }

        .dark .db-mgmt-hero-description {
            color: #94a3b8;
        }

        .db-mgmt-stats-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
        }

        .dark .db-mgmt-stats-card {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(51, 65, 85, 0.8);
        }

        .db-mgmt-stats-header {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark .db-mgmt-stats-header {
            color: #94a3b8;
            border-color: rgba(51, 65, 85, 0.6);
        }

        .db-mgmt-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.85rem;
        }

        .db-mgmt-stat-item {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .db-mgmt-stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
        }

        .dark .db-mgmt-stat-label {
            color: #64748b;
        }

        .db-mgmt-stat-val {
            font-size: 0.875rem;
            font-weight: 700;
            color: #1e293b;
        }

        .dark .db-mgmt-stat-val {
            color: #f1f5f9;
        }

        .db-mgmt-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .db-mgmt-cards-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .db-mgmt-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .db-mgmt-card-title-row {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .db-mgmt-card-icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.65rem;
            flex-shrink: 0;
        }

        .db-mgmt-card-icon-wrap.primary {
            background: #eff6ff;
            color: #2563eb;
        }

        .dark .db-mgmt-card-icon-wrap.primary {
            background: rgba(37, 99, 235, 0.2);
            color: #60a5fa;
        }

        .db-mgmt-card-icon-wrap.success {
            background: #f0fdf4;
            color: #16a34a;
        }

        .dark .db-mgmt-card-icon-wrap.success {
            background: rgba(22, 163, 74, 0.2);
            color: #4ade80;
        }

        .db-mgmt-card-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1.3;
        }

        .dark .db-mgmt-card-title {
            color: #f8fafc;
        }

        .db-mgmt-card-subtitle {
            font-size: 0.75rem;
            color: #64748b;
            margin: 0.15rem 0 0 0;
        }

        .dark .db-mgmt-card-subtitle {
            color: #94a3b8;
        }

        .db-mgmt-card-body {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
            padding-top: 0.5rem;
        }

        .db-mgmt-card-text {
            font-size: 0.85rem;
            line-height: 1.6;
            color: #475569;
            margin: 0;
        }

        .dark .db-mgmt-card-text {
            color: #94a3b8;
        }

        .db-mgmt-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .db-mgmt-primary-btn,
        .db-mgmt-success-btn {
            flex: 1;
            min-width: 180px;
        }

        .db-mgmt-note-box {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.775rem;
            line-height: 1.5;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
        }

        .db-mgmt-note-box.info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .dark .db-mgmt-note-box.info {
            background: rgba(30, 58, 138, 0.25);
            color: #bfdbfe;
            border-color: rgba(59, 130, 246, 0.3);
        }

        .db-mgmt-note-box.success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .dark .db-mgmt-note-box.success {
            background: rgba(20, 83, 45, 0.25);
            color: #bbf7d0;
            border-color: rgba(34, 197, 94, 0.3);
        }

        .db-mgmt-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(0, 0, 0, 0.06);
            padding: 0.1rem 0.35rem;
            border-radius: 0.25rem;
        }

        .dark .db-mgmt-code {
            background: rgba(255, 255, 255, 0.1);
        }

        .db-mgmt-guide-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .db-mgmt-steps-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .db-mgmt-steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .db-mgmt-steps-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .db-mgmt-step-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.65rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .dark .db-mgmt-step-card {
            background: rgba(15, 23, 42, 0.5);
            border-color: rgba(51, 65, 85, 0.6);
        }

        .db-mgmt-step-num {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 9999px;
            background: #3b82f6;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }

        .dark .db-mgmt-step-num {
            background: #2563eb;
        }

        .db-mgmt-step-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .dark .db-mgmt-step-title {
            color: #f8fafc;
        }

        .db-mgmt-step-desc {
            font-size: 0.775rem;
            line-height: 1.5;
            color: #64748b;
            margin: 0;
        }

        .dark .db-mgmt-step-desc {
            color: #94a3b8;
        }
    </style>
</x-filament-panels::page>
