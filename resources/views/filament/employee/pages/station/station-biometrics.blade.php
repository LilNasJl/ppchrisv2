<x-filament-panels::page>
    <style>
        .station-biometrics-shell {
            width: 100%;
            min-height: calc(100vh - 7rem);
        }

        .station-biometrics-frame {
            display: block;
            width: 100%;
            height: calc(100vh - 7rem);
            min-height: 760px;
            border: 0;
            border-radius: 0.5rem;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
        }

        @media (max-width: 768px) {
            .station-biometrics-frame {
                height: calc(100vh - 8rem);
                min-height: 640px;
            }
        }
    </style>

    <div class="station-biometrics-shell">
        <iframe
            src="{{ $this->biometricsUrl }}"
            title="Station Biometrics"
            class="station-biometrics-frame"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
        ></iframe>
    </div>
</x-filament-panels::page>
