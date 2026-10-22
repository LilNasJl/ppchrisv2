<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\PayrollPeriod;
use App\Services\DtrBulkClearService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Override;

class ClearDtrEntries extends Page
{
    protected string $view = 'filament.pages.clear-dtr-entries';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Clear DTR Entries';

    public string $periodId = '';

    public string $branchId = '';

    public string $confirmation = '';

    public string $currentPassword = '';

    #[Locked]
    public string $challenge = '';

    public static function canAccess(): bool
    {
        return auth()->check()
            && in_array(auth()->user()->role, ['hr', 'admin'], true)
            && ! (bool) auth()->user()->is_disabled
            && Dtr::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->rotateChallenge();
    }

    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[Override]
    public function getHeading(): string
    {
        return '';
    }

    #[Override]
    public function getBreadcrumbs(): array
    {
        return [];
    }

    /** @return Collection<int, PayrollPeriod> */
    public function openPeriods(): Collection
    {
        return PayrollPeriod::query()->where('is_locked', false)->newestFirst()->get();
    }

    /** @return Collection<int, Branch> */
    public function branches(): Collection
    {
        return Branch::query()->orderBy('branch_name')->get();
    }

    /** @return array{official:int, visible:int} */
    public function previewCounts(): array
    {
        if (! ctype_digit($this->periodId) || ! ctype_digit($this->branchId)) {
            return ['official' => 0, 'visible' => 0];
        }

        if (! PayrollPeriod::query()->whereKey((int) $this->periodId)->where('is_locked', false)->exists()
            || ! Branch::query()->whereKey((int) $this->branchId)->exists()) {
            return ['official' => 0, 'visible' => 0];
        }

        return app(DtrBulkClearService::class)->counts((int) $this->periodId, (int) $this->branchId);
    }

    public function updatedPeriodId(): void
    {
        $this->rotateChallenge();
    }

    public function updatedBranchId(): void
    {
        $this->rotateChallenge();
    }

    public function clearEntries(): void
    {
        abort_unless(static::canAccess(), 403);

        try {
            $data = $this->validate([
                'periodId' => ['required', 'integer', 'min:1', 'exists:payroll_periods,id'],
                'branchId' => ['required', 'integer', 'min:1', 'exists:branches,id'],
                'confirmation' => ['required', 'string', 'size:8'],
                'currentPassword' => ['required', 'string'],
            ]);

            if (! hash_equals($this->challenge, $data['confirmation'])) {
                throw ValidationException::withMessages(['confirmation' => 'The confirmation code does not match.']);
            }

            $user = auth()->user();
            $rateLimitKey = 'dtr-clear-password:'.$user->getAuthIdentifier();

            if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
                throw ValidationException::withMessages([
                    'currentPassword' => 'Too many password attempts. Try again in '.RateLimiter::availableIn($rateLimitKey).' seconds.',
                ]);
            }

            if (! Hash::check($data['currentPassword'], (string) $user->fresh()?->password)) {
                RateLimiter::hit($rateLimitKey, 300);

                throw ValidationException::withMessages(['currentPassword' => 'The current password is incorrect.']);
            }

            RateLimiter::clear($rateLimitKey);

            $deleted = app(DtrBulkClearService::class)->clear(
                (int) $data['periodId'],
                (int) $data['branchId'],
                $user,
            );

            $this->rotateChallenge();

            Notification::make()
                ->title('DTR entries cleared')
                ->body("Cleared {$deleted['official']} HR entries and {$deleted['visible']} employee-visible entries.")
                ->success()
                ->send();
        } finally {
            $this->currentPassword = '';
        }
    }

    private function rotateChallenge(): void
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $previous = $this->challenge;

        do {
            $code = '';

            for ($index = 0; $index < 8; $index++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while ($code === $previous || ! preg_match('/[A-Z]/', $code) || ! preg_match('/[0-9]/', $code));

        $this->challenge = $code;
        $this->confirmation = '';
        $this->currentPassword = '';
        $this->resetValidation();
    }
}
