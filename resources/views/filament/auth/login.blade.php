@php
    $isEmployeePanel = filament()->getCurrentPanel()?->getId() === 'employee';
    $brandTitle = $isEmployeePanel ? 'SELF SERVICE: HRIS' : 'HRIS';
    $logoCandidates = [
        'image/ppc_logo_circle.png',
        'image/ppc_logo_circle.jpg',
        'image/ppc_logo_circle.jpeg',
        'image/ppc_logo_circle.webp',
        'image/ppc_logo_circle',
    ];
    $logoPath = collect($logoCandidates)
        ->first(fn (string $path): bool => file_exists(public_path($path)));
    $hasLogo = filled($logoPath);
@endphp

<div class="hris-auth-page">
    <style>
        .hris-auth-page {
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .hris-auth-card {
            width: min(920px, 100%);
            min-height: 500px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, .95fr);
            overflow: hidden;
            border: 0;
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 28px 74px rgba(15, 23, 42, .22);
        }

        .hris-auth-form,
        .hris-auth-side {
            min-height: 500px;
        }

        .hris-auth-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(36px, 5vw, 60px);
            background: #ffffff;
        }

        .hris-auth-heading,
        .hris-auth-content {
            width: min(100%, 360px);
        }

        .hris-auth-heading {
            margin: 0 0 28px;
            text-align: center;
            color: #0f172a !important;
            font-size: 30px;
            font-weight: 900;
            line-height: 1.1;
        }

        .hris-mobile-brand,
        .hris-mobile-subtitle {
            display: none;
        }

        .hris-auth-side {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(38px, 5vw, 56px);
            text-align: center;
            background:
                radial-gradient(circle at 50% 28%, rgba(255, 255, 255, .18), transparent 34%),
                linear-gradient(145deg, #1d4ed8 0%, #2563eb 48%, #60a5fa 100%);
        }

        .hris-auth-logo-wrap {
            width: 156px;
            height: 156px;
            display: grid;
            place-items: center;
            margin-bottom: 26px;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: 0 24px 44px rgba(15, 23, 42, .24);
        }

        .hris-auth-logo {
            width: 108px;
            height: 108px;
            display: block;
            object-fit: contain;
            object-position: center;
        }

        .hris-auth-side h2 {
            margin: 0 0 8px;
            color: #ffffff;
            font-size: clamp(20px, 2.5vw, 28px);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: 0;
        }

        .hris-auth-side p {
            max-width: 260px;
            margin: 0;
            color: #eff6ff;
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
            color: #0f172a !important;
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
            border: 1px solid #dbe3ef !important;
            border-radius: 12px !important;
            color: #0f172a !important;
            box-shadow: none !important;
        }

        .hris-auth-form input::placeholder {
            color: #64748b !important;
            opacity: 1 !important;
        }

        .hris-auth-form .fi-input-wrp:focus-within {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .18) !important;
        }

        .hris-auth-form svg {
            color: #2563eb !important;
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
            border-radius: 12px !important;
            background: #2563eb !important;
            color: #ffffff !important;
            font-weight: 900 !important;
            box-shadow: none !important;
        }

        .hris-auth-form button[type="submit"] *,
        .hris-auth-form .fi-btn[type="submit"] * {
            color: #ffffff !important;
        }

        .hris-auth-form button[type="submit"]:hover,
        .hris-auth-form .fi-btn[type="submit"]:hover {
            background: #1d4ed8 !important;
            color: #ffffff !important;
        }

        .dark .hris-auth-card,
        .dark .hris-auth-form {
            border: 0 !important;
        }

        @media (max-width: 768px) {
            .hris-auth-page {
                align-items: center;
                padding: 18px;
            }

            .hris-auth-card {
                grid-template-columns: 1fr;
                min-height: auto;
                border-radius: 22px;
            }

            .hris-auth-form {
                min-height: auto;
                padding: 38px 26px;
                background:
                    radial-gradient(circle at top left, rgba(255, 255, 255, .22), transparent 38%),
                    linear-gradient(135deg, #1d4ed8 0%, #2563eb 54%, #60a5fa 100%) !important;
            }

            .hris-auth-heading {
                margin-bottom: 28px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
                color: #ffffff !important;
                text-align: center;
            }

            .hris-auth-login-title {
                display: none;
            }

            .hris-auth-heading.has-logo::before {
                content: "";
                width: 92px;
                height: 92px;
                display: block;
                border-radius: 999px;
                background-color: #ffffff;
                background-image: url("{{ $hasLogo ? asset($logoPath) : '' }}");
                background-size: 64px 64px;
                background-position: center;
                background-repeat: no-repeat;
                box-shadow: 0 16px 34px rgba(15, 23, 42, .2);
            }

            .hris-mobile-brand {
                display: block;
                color: #ffffff !important;
                font-size: 15px;
                line-height: 1.2;
                font-weight: 900;
                text-align: center;
            }

            .hris-mobile-subtitle {
                display: block;
                color: #eff6ff !important;
                font-size: 13px;
                line-height: 1.4;
                font-weight: 600;
                text-align: center;
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
                color: #ffffff !important;
            }
        }
    </style>

    <main class="hris-auth-card">
        <section class="hris-auth-form">
            <h1 @class(['hris-auth-heading', 'has-logo' => $hasLogo])>
                <span class="hris-auth-login-title">Login</span>
                <span class="hris-mobile-brand">{{ $brandTitle }}</span>
                <span class="hris-mobile-subtitle">Human Resource Information System</span>
            </h1>

            <div class="hris-auth-content">
                {{ $this->content }}
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

            <h2>{{ $brandTitle }}</h2>

            <p>Human Resource Information System</p>
        </aside>
    </main>

    <x-filament-actions::modals />
</div>
