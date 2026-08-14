<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} - {{ $title }} | {{ config('app.name', 'HRIS') }}</title>

    <style>
        :root {
            color-scheme: light dark;
            --page: #f8fbff;
            --surface: #ffffff;
            --border: #dbe7f5;
            --heading: #081b3a;
            --text: #49617f;
            --primary: #1f6fe5;
            --primary-hover: #165fc9;
            --secondary-hover: #edf4ff;
            --shadow: 0 24px 64px rgba(30, 64, 175, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            align-items: center;
            background: var(--page);
            color: var(--text);
            display: flex;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            justify-content: center;
            margin: 0;
            padding: 28px;
        }

        .error-page {
            align-items: center;
            display: grid;
            gap: 48px;
            grid-template-columns: minmax(0, 1.1fr) minmax(300px, 0.9fr);
            max-width: 1120px;
            width: 100%;
        }

        .error-visual {
            align-items: center;
            display: flex;
            justify-content: center;
            min-width: 0;
        }

        .error-visual img {
            display: block;
            height: auto;
            max-height: 660px;
            max-width: 100%;
            object-fit: contain;
            width: 620px;
        }

        .error-content {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 40px;
        }

        .status-label {
            color: var(--primary);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 0;
            margin: 0 0 12px;
            text-transform: uppercase;
        }

        h1 {
            color: var(--heading);
            font-size: clamp(30px, 4vw, 48px);
            line-height: 1.08;
            margin: 0;
        }

        .message {
            font-size: 16px;
            line-height: 1.7;
            margin: 18px 0 0;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 28px;
        }

        .button {
            align-items: center;
            border: 1px solid var(--primary);
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            justify-content: center;
            min-height: 44px;
            padding: 10px 18px;
            text-decoration: none;
        }

        .button-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .button-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .button-secondary {
            background: transparent;
            color: var(--primary);
        }

        .button-secondary:hover {
            background: var(--secondary-hover);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --page: #070d18;
                --surface: #101827;
                --border: #24344c;
                --heading: #f8fbff;
                --text: #afc0d8;
                --primary: #60a5fa;
                --primary-hover: #3b82f6;
                --secondary-hover: #17243a;
                --shadow: 0 24px 64px rgba(0, 0, 0, 0.34);
            }

            .button-primary {
                color: #061123;
            }
        }

        @media (max-width: 820px) {
            body {
                padding: 20px;
            }

            .error-page {
                gap: 18px;
                grid-template-columns: 1fr;
                max-width: 620px;
            }

            .error-visual img {
                max-height: 360px;
                width: 420px;
            }

            .error-content {
                padding: 28px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .error-content {
                padding: 24px 20px;
            }

            .actions,
            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="error-page">
        <div class="error-visual">
            <img src="{{ $image }}" alt="{{ $imageAlt }}">
        </div>

        <section class="error-content" aria-labelledby="error-heading">
            <p class="status-label">Error {{ $status }}</p>
            <h1 id="error-heading">{{ $title }}</h1>
            <p class="message">{{ $message }}</p>

            <div class="actions">
                @if ($status === 503)
                    <a class="button button-primary" href="{{ request()->fullUrl() }}">Try again</a>
                @else
                    <a class="button button-primary" href="{{ url('/') }}">Return to main page</a>
                @endif

                @if ($status === 404)
                    <button class="button button-secondary" type="button" onclick="history.back()">Go back</button>
                @else
                    <a class="button button-secondary" href="{{ url('/') }}">Main page</a>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
