@php($landingBackground = asset('image/hris-blue-wave-background.png'))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Philfumes Petroleum Corporation Human Resource Information System">
    <link rel="icon" type="image/png" href="{{ asset('ppclogo.png') }}?v=20260724">
    <link rel="apple-touch-icon" href="{{ asset('ppclogo.png') }}?v=20260724">
    <title>HRIS | Philfumes Petroleum Corporation</title>

    <style>
        :root {
            color-scheme: light;
            font-family: Inter, Manrope, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
            letter-spacing: 0;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background: #fff;
            color: #0b1c3b;
            min-height: 100vh;
            min-height: 100svh;
        }

        button,
        a {
            font: inherit;
        }

        .landing-shell {
            background-color: #fff;
            background-image: url("{{ $landingBackground }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            min-height: 100vh;
            min-height: 100svh;
            overflow: hidden;
        }

        .content-panel {
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: 30px clamp(38px, 4vw, 72px) 56px;
        }

        .brand {
            align-items: center;
            color: #0d2450;
            display: inline-flex;
            gap: 10px;
            text-decoration: none;
            width: fit-content;
        }

        .brand-logo {
            display: block;
            height: 64px;
            object-fit: contain;
            width: 64px;
        }

        .brand-copy {
            display: grid;
            gap: 2px;
        }

        .brand-name {
            color: #071a3a;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.2;
            max-width: 250px;
        }

        .brand-system {
            color: #3869ae;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.3;
        }

        .content-main {
            align-items: center;
            display: flex;
            flex: 1;
            padding: 32px 0 34px;
        }

        .hero {
            max-width: 590px;
            width: 100%;
        }

        h1 {
            color: #071a3a;
            font-size: 58px;
            font-weight: 850;
            line-height: 1.02;
            margin: 0;
            max-width: 590px;
            text-wrap: balance;
        }

        .title-accent {
            color: #1268d9;
            display: block;
        }

        .hero-summary {
            color: #17355f;
            font-size: 21px;
            font-weight: 750;
            line-height: 1.35;
            margin: 22px 0 0;
            max-width: 540px;
        }

        .hero-description {
            color: #5b6b82;
            font-size: 14px;
            line-height: 1.65;
            margin: 9px 0 0;
            max-width: 550px;
        }

        .feature-grid {
            border-bottom: 1px solid #dbe5f2;
            border-top: 1px solid #dbe5f2;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 24px;
        }

        .feature-item {
            min-width: 0;
            padding: 13px 14px;
        }

        .feature-item:first-child {
            padding-left: 0;
        }

        .feature-item + .feature-item {
            border-left: 1px solid #e2eaf4;
        }

        .feature-title {
            color: #102b56;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.35;
            margin: 0;
        }

        .feature-copy {
            color: #68778c;
            font-size: 10px;
            line-height: 1.45;
            margin: 4px 0 0;
        }

        .portal-heading {
            color: #526b90;
            font-size: 11px;
            font-weight: 800;
            margin: 24px 0 10px;
            text-transform: uppercase;
        }

        .portal-actions {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .portal-link {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 7px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            justify-content: center;
            min-height: 50px;
            padding: 0 18px;
            text-align: center;
            text-decoration: none;
            transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
        }

        .portal-link:hover {
            transform: translateY(-1px);
        }

        .portal-link:focus-visible,
        .carousel-button:focus-visible,
        .carousel-dot:focus-visible {
            outline: 3px solid rgba(20, 112, 227, .45);
            outline-offset: 3px;
        }

        .portal-link-primary {
            background: #1268d9;
            box-shadow: 0 12px 28px rgba(18, 104, 217, .2);
            color: #fff;
        }

        .portal-link-primary:hover {
            background: #0c55b7;
        }

        .portal-link-secondary {
            background: #fff;
            border-color: #8bb2e5;
            color: #124f9f;
        }

        .portal-link-secondary:hover {
            background: #eef6ff;
            border-color: #3979c9;
        }

        .access-note {
            color: #758297;
            font-size: 10px;
            line-height: 1.5;
            margin: 11px 0 0;
        }

        .visual-panel {
            align-items: center;
            display: flex;
            justify-content: center;
            min-width: 0;
            overflow: hidden;
            padding: 56px clamp(38px, 4vw, 76px);
            position: relative;
        }

        .carousel {
            max-width: 680px;
            position: relative;
            width: 100%;
        }

        .carousel-label {
            background: rgba(3, 43, 119, .76);
            border: 1px solid rgba(255, 255, 255, .34);
            border-radius: 4px;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
            margin: 0 0 12px;
            padding: 7px 10px;
            text-transform: uppercase;
            width: fit-content;
        }

        .carousel-stage {
            animation: carousel-float 6s ease-in-out infinite;
            aspect-ratio: 16 / 10;
            background: #082762;
            border: 1px solid rgba(255, 255, 255, .58);
            border-radius: 8px;
            box-shadow: 0 30px 70px rgba(1, 25, 82, .34);
            overflow: hidden;
            position: relative;
        }

        .carousel-slide {
            inset: 0;
            opacity: 0;
            position: absolute;
            transform: translateX(28px) scale(.985);
            transition: opacity .55s ease, transform .55s ease;
            visibility: hidden;
        }

        .carousel-slide.is-active {
            opacity: 1;
            transform: translateX(0) scale(1);
            visibility: visible;
        }

        .carousel-slide img {
            display: block;
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .slide-caption {
            background: linear-gradient(180deg, transparent, rgba(2, 15, 48, .9));
            bottom: 0;
            color: #fff;
            left: 0;
            padding: 54px 28px 24px;
            position: absolute;
            right: 0;
        }

        .slide-caption h2 {
            font-size: 25px;
            font-weight: 820;
            line-height: 1.15;
            margin: 0;
        }

        .slide-caption p {
            color: #d9eaff;
            font-size: 12px;
            line-height: 1.55;
            margin: 6px 0 0;
            max-width: 480px;
        }

        .carousel-controls {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
        }

        .carousel-arrows {
            display: flex;
            gap: 8px;
        }

        .carousel-button {
            align-items: center;
            background: rgba(3, 38, 112, .34);
            border: 1px solid rgba(255, 255, 255, .54);
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-size: 18px;
            height: 42px;
            justify-content: center;
            transition: background-color .18s ease, border-color .18s ease, transform .18s ease;
            width: 42px;
        }

        .carousel-button:hover {
            background: rgba(255, 255, 255, .2);
            border-color: #fff;
            transform: translateY(-1px);
        }

        .carousel-dots {
            align-items: center;
            display: flex;
            gap: 8px;
        }

        .carousel-dot {
            background: rgba(255, 255, 255, .38);
            border: 0;
            border-radius: 50%;
            cursor: pointer;
            height: 8px;
            padding: 0;
            transition: background-color .18s ease, transform .18s ease;
            width: 8px;
        }

        .carousel-dot.is-active {
            background: #fff;
            transform: scale(1.35);
        }

        .landing-footer {
            bottom: 18px;
            color: #708096;
            font-size: 10px;
            left: clamp(38px, 4vw, 72px);
            line-height: 1.4;
            position: fixed;
            z-index: 3;
        }

        @keyframes carousel-float {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @media (min-width: 1500px) {
            h1 {
                font-size: 64px;
            }

            .hero-summary {
                font-size: 22px;
            }
        }

        @media (min-width: 801px) and (max-width: 1120px) {
            .landing-shell {
                background-position: 35% center;
            }

            .content-panel {
                padding-left: 34px;
                padding-right: 34px;
            }

            h1 {
                font-size: 44px;
            }

            .hero-summary {
                font-size: 18px;
            }

            .portal-actions {
                grid-template-columns: 1fr;
            }

            .visual-panel {
                padding-left: 34px;
                padding-right: 34px;
            }
        }

        @media (max-width: 800px) {
            .landing-shell {
                background-color: #0758cf;
                background-image: url("{{ $landingBackground }}");
                background-position: 78% center;
                background-repeat: no-repeat;
                background-size: cover;
                display: block;
                overflow: visible;
            }

            .content-panel {
                background: transparent;
                min-height: auto;
                padding: 0;
            }

            .content-panel > header {
                padding: 22px 22px 30px;
            }

            .brand {
                color: #fff;
            }

            .brand-logo {
                filter: brightness(0) invert(1);
                height: 58px;
                width: 58px;
            }

            .brand-name {
                color: #fff;
                font-size: 13px;
                max-width: 210px;
            }

            .brand-system {
                color: #dbeeff;
                font-size: 10px;
            }

            .content-main {
                background: #fff;
                border-top: 1px solid rgba(255, 255, 255, .72);
                padding: 44px 22px 38px;
            }

            h1 {
                font-size: 42px;
                line-height: 1.05;
            }

            .hero-summary {
                font-size: 19px;
                margin-top: 20px;
            }

            .hero-description {
                font-size: 14px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
                margin-top: 24px;
            }

            .feature-item,
            .feature-item:first-child {
                padding: 11px 0;
            }

            .feature-item + .feature-item {
                border-left: 0;
                border-top: 1px solid #e2eaf4;
            }

            .feature-title {
                font-size: 13px;
            }

            .feature-copy {
                font-size: 11px;
            }

            .portal-actions {
                grid-template-columns: 1fr;
            }

            .portal-link {
                width: 100%;
            }

            .visual-panel {
                background: transparent;
                min-height: 520px;
                padding: 54px 18px;
            }

            .carousel {
                max-width: 540px;
            }

            .carousel-stage {
                aspect-ratio: 4 / 3;
            }

            .slide-caption {
                padding: 44px 20px 18px;
            }

            .slide-caption h2 {
                font-size: 20px;
            }

            .slide-caption p {
                font-size: 11px;
            }

            .landing-footer {
                background: #063eab;
                bottom: auto;
                color: rgba(255, 255, 255, .78);
                left: auto;
                padding: 16px 20px;
                position: static;
                text-align: center;
            }
        }

        @media (max-width: 420px) {
            .content-panel {
                padding: 0;
            }

            .content-panel > header {
                padding: 20px 18px 26px;
            }

            .content-main {
                padding: 40px 18px 34px;
            }

            h1 {
                font-size: 36px;
            }

            .hero-summary {
                font-size: 18px;
            }

            .visual-panel {
                min-height: 470px;
                padding-left: 14px;
                padding-right: 14px;
            }

            .carousel-controls {
                margin-top: 14px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }

            .carousel-stage {
                animation: none;
            }
        }
    </style>
</head>
<body>
    <div class="landing-shell">
        <section class="content-panel">
            <header>
                <a href="{{ route('landing') }}" class="brand" aria-label="Philfumes HRIS home">
                    <img src="{{ asset('image/ppcblueblack.png') }}" alt="PhilFumes logo" class="brand-logo">
                    <span class="brand-copy">
                        <span class="brand-name">Philfumes Petroleum Corporation</span>
                        <span class="brand-system">Human Resource Information System</span>
                    </span>
                </a>
            </header>

            <main class="content-main">
                <div class="hero">
                    <h1>
                        Human Resource
                        <span class="title-accent">Information System</span>
                    </h1>
                    <p class="hero-summary">Everything your people operations need, in one secure place.</p>
                    <!-- <p class="hero-description">
                        Manage employee records, D.T.R., payroll, leave, loans, benefits, notices, and self-service access through one connected workspace.
                    </p> -->

                    <div class="feature-grid" aria-label="HRIS capabilities">
                        <div class="feature-item">
                            <p class="feature-title">People records</p>
                            <p class="feature-copy">Profiles, employment, compliance, and benefits.</p>
                        </div>
                        <div class="feature-item">
                            <p class="feature-title">Time and pay</p>
                            <p class="feature-copy">D.T.R., leave, payroll, loans, and payslips.</p>
                        </div>
                        <div class="feature-item">
                            <p class="feature-title">Self-service</p>
                            <p class="feature-copy">Requests, notices, tickets, and personal access.</p>
                        </div>
                    </div>

                    <p class="portal-heading">Choose your secure portal</p>
                    <nav class="portal-actions" aria-label="Login portals">
                        <a href="{{ route('filament.hr.auth.login') }}" class="portal-link portal-link-primary" data-portal="hr">
                            HR Portal
                        </a>
                        <a href="{{ route('filament.employee.auth.login') }}" class="portal-link portal-link-secondary" data-portal="employee">
                            Self-Service
                        </a>
                        <a href="{{ route('filament.kpi.auth.login') }}" class="portal-link portal-link-secondary" data-portal="kpi">
                            KPI Portal
                        </a>
                        <a href="{{ route('filament.sicrc.auth.login') }}" class="portal-link portal-link-secondary" data-portal="sicrc">
                            SIC / RC Portal
                        </a>
                    </nav>
                    <p class="access-note">Authorized access for Philfumes HR teams, employees, assigned KPI raters, and SIC / RC users.</p>
                </div>
            </main>
        </section>

        <aside class="visual-panel" aria-label="HRIS highlights">
            <div class="carousel" data-carousel aria-roledescription="carousel" aria-label="HRIS capabilities">
                <p class="carousel-label">Connected workforce operations</p>

                <div class="carousel-stage" aria-live="polite">
                    <article class="carousel-slide is-active" data-slide aria-hidden="false">
                        <img src="{{ asset('image/hris-carousel-people.png') }}" alt="HR team reviewing employee information">
                        <div class="slide-caption">
                            <h2>People records, clearly connected</h2>
                            <p>Keep essential employee information organized, current, and accessible to the right team.</p>
                        </div>
                    </article>

                    <article class="carousel-slide" data-slide aria-hidden="true">
                        <img src="{{ asset('image/hris-carousel-payroll.png') }}" alt="HR specialist reviewing payroll and attendance information">
                        <div class="slide-caption">
                            <h2>From attendance to payroll</h2>
                            <p>Bring D.T.R., schedules, leave, payroll periods, and pay details into one reliable flow.</p>
                        </div>
                    </article>

                    <article class="carousel-slide" data-slide aria-hidden="true">
                        <img src="{{ asset('image/hris-carousel-self-service.png') }}" alt="Employee using a self-service HR portal">
                        <div class="slide-caption">
                            <h2>Self-service that stays simple</h2>
                            <p>Give employees secure access to their information, requests, payslips, notices, and updates.</p>
                        </div>
                    </article>
                </div>

                <div class="carousel-controls">
                    <div class="carousel-arrows">
                        <button type="button" class="carousel-button" data-previous aria-label="Previous slide" title="Previous slide">
                            &larr;
                        </button>
                        <button type="button" class="carousel-button" data-next aria-label="Next slide" title="Next slide">
                            &rarr;
                        </button>
                    </div>

                    <div class="carousel-dots" aria-label="Select carousel slide">
                        <button type="button" class="carousel-dot is-active" data-dot="0" aria-label="Show slide 1" aria-current="true"></button>
                        <button type="button" class="carousel-dot" data-dot="1" aria-label="Show slide 2" aria-current="false"></button>
                        <button type="button" class="carousel-dot" data-dot="2" aria-label="Show slide 3" aria-current="false"></button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <footer class="landing-footer">
        &copy; {{ now()->year }} Philfumes Petroleum Corporation
    </footer>

    <script>
        (() => {
            const carousel = document.querySelector('[data-carousel]');

            if (! carousel) {
                return;
            }

            const slides = Array.from(carousel.querySelectorAll('[data-slide]'));
            const dots = Array.from(carousel.querySelectorAll('[data-dot]'));
            const previous = carousel.querySelector('[data-previous]');
            const next = carousel.querySelector('[data-next]');
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            let activeIndex = 0;
            let timer = null;

            const stopAutoPlay = () => {
                if (timer !== null) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };

            const startAutoPlay = () => {
                stopAutoPlay();

                if (reduceMotion.matches || slides.length < 2 || document.hidden) {
                    return;
                }

                timer = window.setInterval(() => {
                    showSlide(activeIndex + 1);
                }, 5500);
            };

            const showSlide = (requestedIndex, restart = false) => {
                activeIndex = (requestedIndex + slides.length) % slides.length;

                slides.forEach((slide, index) => {
                    const isActive = index === activeIndex;

                    slide.classList.toggle('is-active', isActive);
                    slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                });

                dots.forEach((dot, index) => {
                    const isActive = index === activeIndex;

                    dot.classList.toggle('is-active', isActive);
                    dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                });

                if (restart) {
                    startAutoPlay();
                }
            };

            previous?.addEventListener('click', () => showSlide(activeIndex - 1, true));
            next?.addEventListener('click', () => showSlide(activeIndex + 1, true));

            dots.forEach((dot) => {
                dot.addEventListener('click', () => {
                    showSlide(Number(dot.dataset.dot), true);
                });
            });

            carousel.addEventListener('mouseenter', stopAutoPlay);
            carousel.addEventListener('mouseleave', startAutoPlay);
            carousel.addEventListener('focusin', stopAutoPlay);
            carousel.addEventListener('focusout', (event) => {
                if (! carousel.contains(event.relatedTarget)) {
                    startAutoPlay();
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopAutoPlay();
                } else {
                    startAutoPlay();
                }
            });

            reduceMotion.addEventListener?.('change', startAutoPlay);
            showSlide(0);
            startAutoPlay();
        })();
    </script>
</body>
</html>
