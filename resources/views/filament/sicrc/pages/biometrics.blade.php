<x-filament-panels::page>
    <style>
        .sicrc-biometrics-shell {
            width: 100%;
            min-height: calc(100vh - 7rem);
        }

        .sicrc-biometrics-frame {
            display: block;
            width: 100%;
            height: calc(100vh - 7rem);
            min-height: 760px;
            border: 0;
            border-radius: 0;
            background: #ffffff;
        }

        @media (max-width: 768px) {
            .sicrc-biometrics-frame {
                height: calc(100vh - 8rem);
                min-height: 640px;
            }
        }
    </style>

    <div class="sicrc-biometrics-shell">
        <iframe
            src="{{ $this->biometricsUrl }}"
            title="Biometrics"
            class="sicrc-biometrics-frame"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
        ></iframe>
    </div>
</x-filament-panels::page>