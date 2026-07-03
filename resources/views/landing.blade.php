<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Philfumes Petroleum Corporation Human Resource Information System">
    <title>HRIS | Philfumes Petroleum Corporation</title>

    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background-color: #020817;
            background-image: url('{{ asset('image/hris-landing-background.png') }}');
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            color: #fff;
            min-height: 100vh;
            min-height: 100svh;
            position: relative;
        }

        body::before {
            background: rgba(1, 7, 20, .42);
            content: "";
            inset: 0;
            pointer-events: none;
            position: fixed;
        }

        .landing-page {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100svh;
            position: relative;
            z-index: 1;
        }

        .landing-header,
        .landing-main,
        .landing-footer {
            margin: 0 auto;
            max-width: 1360px;
            width: 100%;
        }

        .landing-header {
            align-items: center;
            display: flex;
            justify-content: space-between;
            padding: 28px 48px;
        }

        .brand {
            align-items: center;
            color: #fff;
            display: inline-flex;
            gap: 13px;
            text-decoration: none;
        }

        .brand-logo {
            background: #fff;
            border-radius: 8px;
            display: block;
            height: 54px;
            object-fit: contain;
            padding: 5px;
            width: 54px;
        }

        .brand-name {
            font-size: 15px;
            font-weight: 750;
            line-height: 1.25;
            max-width: 250px;
        }

        .brand-system {
            color: #a9c9ff;
            display: block;
            font-size: 11px;
            font-weight: 650;
            margin-top: 2px;
        }

        .landing-main {
            align-items: center;
            display: flex;
            flex: 1;
            padding: 44px 48px 96px;
        }

        .hero-content {
            max-width: 720px;
        }

        .hero-label {
            color: #9fc5ff;
            font-size: 13px;
            font-weight: 750;
            margin: 0 0 16px;
            text-transform: uppercase;
        }

        h1 {
            font-size: 64px;
            font-weight: 800;
            line-height: 1.03;
            margin: 0;
            max-width: 700px;
            text-wrap: balance;
        }

        .hero-description {
            color: #d8e7ff;
            font-size: 18px;
            line-height: 1.65;
            margin: 24px 0 0;
            max-width: 650px;
        }

        .portal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 34px;
        }

        .portal-link {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 7px;
            display: inline-flex;
            font-size: 14px;
            font-weight: 750;
            justify-content: center;
            min-height: 48px;
            padding: 0 22px;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease, transform .15s ease;
        }

        .portal-link:hover {
            transform: translateY(-1px);
        }

        .portal-link:focus-visible {
            outline: 3px solid rgba(147, 197, 253, .8);
            outline-offset: 3px;
        }

        .portal-link-primary {
            background: #fff;
            color: #0b3b91;
        }

        .portal-link-primary:hover {
            background: #dbeafe;
        }

        .portal-link-secondary {
            background: rgba(2, 8, 23, .38);
            border-color: rgba(255, 255, 255, .62);
            color: #fff;
        }

        .portal-link-secondary:hover {
            background: rgba(255, 255, 255, .12);
            border-color: #fff;
        }

        .landing-footer {
            color: #a9c9ff;
            font-size: 12px;
            padding: 20px 48px 28px;
        }

        @media (max-width: 800px) {
            body {
                background-position: 66% center;
            }

            body::before {
                background: rgba(1, 7, 20, .58);
            }

            .landing-header {
                padding: 22px 24px;
            }

            .landing-main {
                align-items: flex-start;
                padding: 72px 24px 72px;
            }

            h1 {
                font-size: 44px;
            }

            .hero-description {
                font-size: 16px;
                max-width: 560px;
            }

            .landing-footer {
                padding: 18px 24px 24px;
            }
        }

        @media (max-width: 520px) {
            .brand-logo {
                height: 46px;
                width: 46px;
            }

            .brand-name {
                font-size: 13px;
                max-width: 210px;
            }

            .brand-system {
                font-size: 10px;
            }

            .landing-main {
                padding-top: 54px;
            }

            .hero-label {
                font-size: 11px;
                margin-bottom: 12px;
            }

            h1 {
                font-size: 36px;
                line-height: 1.08;
            }

            .hero-description {
                font-size: 15px;
                line-height: 1.55;
                margin-top: 18px;
            }

            .portal-actions {
                display: grid;
                margin-top: 28px;
                width: 100%;
            }

            .portal-link {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <header class="landing-header">
            <a href="{{ route('landing') }}" class="brand" aria-label="Philfumes HRIS home">
                <img src="{{ asset('image/ppclogo.png') }}" alt="Philfumes logo" class="brand-logo">
                <span class="brand-name">
                    Philfumes Petroleum Corporation
                    <span class="brand-system">Human Resource Information System</span>
                </span>
            </a>
        </header>

        <main class="landing-main">
            <section class="hero-content" aria-labelledby="hero-title">
                <p class="hero-label">People and workforce operations</p>
                <h1 id="hero-title">Human Resource Information System</h1>
                <p class="hero-description">
                    A secure workspace for employee records, attendance, payroll, leave, and everyday workforce operations.
                </p>

                <nav class="portal-actions" aria-label="Login portals">
                    <a href="{{ route('filament.hr.auth.login') }}" class="portal-link portal-link-primary">
                        HR Portal
                    </a>
                    <a href="{{ route('filament.employee.auth.login') }}" class="portal-link portal-link-secondary">
                        Employee Self-Service
                    </a>
                </nav>
            </section>
        </main>

        <footer class="landing-footer">
            Philfumes Petroleum Corporation
        </footer>
    </div>
</body>
</html>
