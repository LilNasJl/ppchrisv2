@php
    $panelId = filament()->getCurrentPanel()?->getId();
    $brandTitle = match ($panelId) {
        'employee' => 'HRIS: SELF SERVICE',
        'kpi' => 'KPI PORTAL',
        default => 'HRIS',
    };
    $brandSubtitle = 'Human Resource Information System';
    $logoCandidates = [
        'image/ppcwhite.png',
        'image/ppc_og_logo.png',
        'image/ppclogo.png',
    ];
    $logoPath = collect($logoCandidates)
        ->first(fn (string $path): bool => file_exists(public_path($path)));
    $hasLogo = filled($logoPath);
@endphp

<div class="hris-auth-page">
    <style>
        .hris-auth-page {
            position: relative;
            isolation: isolate;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 36px clamp(28px, 4vw, 72px);
            overflow: hidden;
            background-color: #ffffff;
            background-image: url("{{ asset('image/hris-blue-wave-background.png') }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            color: #0b1c3b;
        }

        .hris-auth-page::before {
            display: none;
        }

        .hris-auth-card {
            width: min(1180px, 100%);
            min-height: 610px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .hris-auth-form,
        .hris-auth-side {
            min-height: 610px;
        }

        .hris-auth-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(38px, 5vw, 68px);
            border: 1px solid rgba(205, 220, 240, .78);
            border-radius: 12px;
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 24px 56px rgba(17, 62, 126, .16);
        }

        .hris-auth-heading,
        .hris-auth-content {
            width: min(100%, 360px);
        }

        .hris-auth-heading {
            margin: 0 0 28px;
            text-align: left;
            color: #071a3a !important;
            font-size: 34px;
            font-weight: 850;
            line-height: 1.12;
        }

        .hris-auth-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(38px, 5vw, 56px);
            text-align: center;
            background: transparent;
        }

        .hris-auth-logo-wrap {
            width: min(250px, 68%);
            height: 175px;
            display: grid;
            place-items: center;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .hris-auth-logo {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
            object-position: center;
            filter: drop-shadow(0 14px 28px rgba(4, 36, 95, .2));
        }

        .hris-auth-login-title,
        .hris-auth-form-subtitle {
            display: block;
        }

        .hris-auth-form-subtitle {
            margin-top: 8px;
            color: #52709b;
            font-size: 14px;
            line-height: 1.55;
            font-weight: 600;
        }

        .hris-auth-form label,
        .hris-auth-form label span,
        .hris-auth-form .fi-label,
        .hris-auth-form .fi-label span,
        .hris-auth-form .fi-fo-field-wrp-label,
        .hris-auth-form .fi-fo-field-wrp-label span,
        .hris-auth-form .fi-fo-field-wrp-label label,
        .hris-auth-form .fi-fo-field-wrp-helper-text,
        .hris-auth-form .fi-fo-field-wrp-hint,
        .hris-auth-form .fi-checkbox-label,
        .hris-auth-form .fi-fo-checkbox-list-option-label {
            color: #17355f !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .hris-auth-form .fi-form,
        .hris-auth-form form {
            width: 100%;
        }

        .hris-auth-form .fi-form {
            gap: 18px;
        }

        .hris-auth-form .fi-fo-field-wrp-label {
            display: flex !important;
        }

        .hris-auth-form input,
        .hris-auth-form .fi-input,
        .hris-auth-form .fi-input-wrp input,
        .hris-auth-form input[type="text"],
        .hris-auth-form input[type="email"],
        .hris-auth-form input[type="password"] {
            min-height: 44px;
            color: #0f172a !important;
            background-color: #ffffff !important;
            -webkit-text-fill-color: #0f172a !important;
            font-weight: 700;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .hris-auth-form .fi-input-wrp,
        .hris-auth-form .fi-input-wrp-input {
            background-color: #ffffff !important;
            border: 1px solid #cbd9eb !important;
            border-radius: 8px !important;
            color: #0f172a !important;
            box-shadow: 0 7px 18px rgba(27, 73, 137, .06) !important;
        }

        .hris-auth-form input::placeholder {
            color: #cbd5e1 !important;
            -webkit-text-fill-color: #cbd5e1 !important;
            font-weight: 400 !important;
            opacity: .9 !important;
        }

        .hris-auth-form .fi-input-wrp:focus-within {
            border-color: #1268d9 !important;
            box-shadow: 0 0 0 3px rgba(18, 104, 217, .14) !important;
        }

        .hris-auth-form svg {
            color: #1268d9 !important;
        }

        .hris-auth-form .fi-fo-field-wrp-error-message,
        .hris-auth-form .fi-fo-field-wrp-error-message *,
        .hris-auth-form .fi-invalid,
        .hris-auth-form .fi-invalid *,
        .hris-auth-form [data-validation-error],
        .hris-auth-form [data-validation-error] *,
        .hris-auth-form [role="alert"],
        .hris-auth-form [role="alert"] * {
            color: #dc2626 !important;
            -webkit-text-fill-color: #dc2626 !important;
        }

        .hris-auth-form .fi-checkbox,
        .hris-auth-form .fi-checkbox-list,
        .hris-auth-form .fi-fo-checkbox-list,
        .hris-auth-form .fi-checkbox-list-option,
        .hris-auth-form .fi-fo-checkbox-list-option,
        .hris-auth-form label:has(input[type="checkbox"]) {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 8px !important;
        }

        .hris-auth-form .fi-checkbox-input,
        .hris-auth-form input[type="checkbox"] {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            margin: 0 !important;
            flex: 0 0 16px !important;
            color: #2563eb !important;
            background-color: #ffffff !important;
            border-color: #94a3b8 !important;
        }

        .hris-auth-form input[type="checkbox"]:checked {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
        }

        .hris-auth-form .fi-checkbox-label,
        .hris-auth-form input[type="checkbox"] + span {
            display: inline !important;
            margin: 0 !important;
            line-height: 1.3 !important;
        }

        .hris-auth-form .fi-ac {
            justify-content: stretch;
        }

        .hris-auth-form button[type="submit"],
        .hris-auth-form .fi-btn[type="submit"] {
            width: 100%;
            min-height: 46px;
            border: 0 !important;
            border-radius: 8px !important;
            background: #1268d9 !important;
            color: #ffffff !important;
            font-weight: 900 !important;
            box-shadow: 0 12px 28px rgba(18, 104, 217, .2) !important;
        }

        .hris-auth-form button[type="submit"] *,
        .hris-auth-form .fi-btn[type="submit"] * {
            color: #ffffff !important;
        }

        .hris-auth-form button[type="submit"]:hover,
        .hris-auth-form .fi-btn[type="submit"]:hover {
            background: #0c55b7 !important;
            color: #ffffff !important;
        }

        .dark .hris-auth-card,
        .dark .hris-auth-form {
            border: 0 !important;
        }

        .dark .hris-auth-heading {
            color: #071a3a !important;
        }

        .hris-auth-home-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin: 18px auto 0;
            color: #124f9f !important;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            transition: color .2s ease, transform .2s ease;
        }

        .hris-auth-home-link:hover {
            color: #0b376f !important;
            transform: translateX(-2px);
        }

        .hris-auth-home-link svg {
            width: 16px;
            height: 16px;
        }

        .dark .hris-auth-form label,
        .dark .hris-auth-form label span,
        .dark .hris-auth-form .fi-label,
        .dark .hris-auth-form .fi-label span,
        .dark .hris-auth-form .fi-fo-field-wrp-label,
        .dark .hris-auth-form .fi-fo-field-wrp-label span,
        .dark .hris-auth-form .fi-fo-field-wrp-label label,
        .dark .hris-auth-form .fi-fo-field-wrp-helper-text,
        .dark .hris-auth-form .fi-fo-field-wrp-hint,
        .dark .hris-auth-form .fi-checkbox-label,
        .dark .hris-auth-form .fi-fo-checkbox-list-option-label {
            color: #17355f !important;
            -webkit-text-fill-color: #17355f !important;
        }

        @media (max-width: 768px) {
            .hris-auth-page {
                align-items: center;
                padding: 22px 18px;
                background-color: #1268d9;
                background-position: 76% center;
            }

            .hris-auth-card {
                grid-template-columns: 1fr;
                min-height: auto;
                border-radius: 8px;
            }

            .hris-auth-form {
                min-height: auto;
                padding: 34px 24px;
                border: 1px solid rgba(255, 255, 255, .75);
                border-radius: 8px;
                background: rgba(255, 255, 255, .97) !important;
                box-shadow: 0 22px 52px rgba(5, 40, 105, .22);
            }

            .hris-auth-heading {
                margin-bottom: 28px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                color: #071a3a !important;
                text-align: center;
            }

            .hris-auth-login-title {
                display: block;
                font-size: 15px;
            }

            .hris-auth-form-subtitle {
                display: none !important;
            }

            .hris-auth-heading.has-logo::before {
                content: "";
                width: 138px;
                height: 94px;
                display: block;
                border-radius: 0;
                background-color: transparent;
                background-image: url("{{ $hasLogo ? asset($logoPath) : '' }}");
                background-size: contain;
                background-position: center;
                background-repeat: no-repeat;
                filter: brightness(0) saturate(100%) invert(26%) sepia(97%) saturate(1979%) hue-rotate(204deg) brightness(91%) contrast(94%);
            }

            .hris-auth-side {
                display: none;
            }

            .hris-auth-form label,
            .hris-auth-form label span,
            .hris-auth-form .fi-label,
            .hris-auth-form .fi-label span,
            .hris-auth-form .fi-fo-field-wrp-label,
            .hris-auth-form .fi-fo-field-wrp-label span,
            .hris-auth-form .fi-checkbox-label {
                color: #17355f !important;
            }
        }
    </style>

    <main class="hris-auth-card">
        <section class="hris-auth-form">
            <h1 @class(['hris-auth-heading', 'has-logo' => $hasLogo])>
                <span class="hris-auth-login-title">{{ $brandTitle }}</span>
                <span class="hris-auth-form-subtitle">{{ $brandSubtitle }}</span>
            </h1>

            <div class="hris-auth-content">
                {{ $this->content }}

                <a href="{{ route('landing') }}" class="hris-auth-home-link">
                    <x-filament::icon icon="heroicon-m-arrow-left" />
                    <span>Return to main page</span>
                </a>
            </div>
        </section>

        <aside class="hris-auth-side">
            @if ($hasLogo)
                <div class="hris-auth-logo-wrap">
                    <img
                        src="{{ asset($logoPath) }}"
                        alt="Philfumes Petroleum Corporation"
                        class="hris-auth-logo"
                    >
                </div>
            @endif

        </aside>
    </main>

    <x-filament-actions::modals />
</div>
