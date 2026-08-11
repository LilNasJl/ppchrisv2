<x-filament-panels::page>
    <div class="database-management-panel">
        <div>
            <p class="database-management-eyebrow">Backup and restore</p>
            <h2>Protect and recover HRIS data</h2>
            <p class="database-management-copy">
                Download the database alone or create an encrypted full-system archive containing the database and managed uploads. Full restoration validates every archived file before replacing current data.
            </p>
        </div>

        <dl class="database-management-details">
            <div>
                <dt>Format</dt>
                <dd>MySQL SQL or encrypted ZIP</dd>
            </div>
            <div>
                <dt>Protection</dt>
                <dd>Password confirmation and AES-256 encryption</dd>
            </div>
            <div>
                <dt>Full backup scope</dt>
                <dd>Database, profile photos, memos, leave files, and ticket attachments</dd>
            </div>
        </dl>
    </div>

    <style>
        .database-management-panel {
            align-items: start;
            border-block: 1px solid rgb(226 232 240);
            display: grid;
            gap: 2rem;
            grid-template-columns: minmax(0, 1.3fr) minmax(280px, .7fr);
            padding-block: 2rem;
        }

        .dark .database-management-panel {
            border-color: rgb(51 65 85);
        }

        .database-management-eyebrow {
            color: rgb(37 99 235);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: 0;
            margin: 0 0 .5rem;
            text-transform: uppercase;
        }

        .database-management-panel h2 {
            color: rgb(15 23 42);
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
        }

        .dark .database-management-panel h2 {
            color: rgb(248 250 252);
        }

        .database-management-copy {
            color: rgb(71 85 105);
            line-height: 1.65;
            margin: .75rem 0 0;
            max-width: 68ch;
        }

        .dark .database-management-copy {
            color: rgb(203 213 225);
        }

        .database-management-details {
            display: grid;
            gap: .75rem;
            margin: 0;
        }

        .database-management-details div {
            border-left: 3px solid rgb(37 99 235);
            padding-left: .875rem;
        }

        .database-management-details dt {
            color: rgb(100 116 139);
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .database-management-details dd {
            color: rgb(15 23 42);
            font-size: .9rem;
            font-weight: 700;
            margin: .15rem 0 0;
        }

        .dark .database-management-details dt {
            color: rgb(148 163 184);
        }

        .dark .database-management-details dd {
            color: rgb(241 245 249);
        }

        @media (max-width: 760px) {
            .database-management-panel {
                grid-template-columns: 1fr;
                padding-block: 1.25rem;
            }
        }
    </style>
</x-filament-panels::page>
