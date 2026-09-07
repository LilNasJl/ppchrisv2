<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class PortalLoginResponse implements LoginResponse
{
    public function __construct(private readonly string $destination) {}

    public function toResponse($request): RedirectResponse|Redirector
    {
        session()->forget('url.intended');

        return redirect()->to($this->destination);
    }
}
