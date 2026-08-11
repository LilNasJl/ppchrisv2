<x-filament-panels::page>
    <div class="biometrics-frame-shell">
        <iframe
            class="biometrics-frame"
            src="{{ $biometricsUrl }}"
            title="PhilFumes Biometrics Management"
            loading="eager"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>

    <style>
        .biometrics-frame-shell {
            width: 100%;
            height: max(36rem, calc(100dvh - 13rem));
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
            border-radius: 8px;
            background: #ffffff;
        }

        .dark .biometrics-frame-shell {
            border-color: rgb(39 39 42);
            background: #09090b;
        }

        .biometrics-frame {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
            background: #ffffff;
        }

        @@media (max-width: 640px) {
            .biometrics-frame-shell {
                height: max(32rem, calc(100dvh - 11rem));
            }
        }
    </style>
</x-filament-panels::page>
