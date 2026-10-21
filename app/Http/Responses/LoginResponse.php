<?php

namespace App\Http\Responses;

use App\Filament\Pages\EntryPage;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

// Staff land on the entry screen after login; admins (no entry screen) keep the default.
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect()->to(static::url());
    }

    public static function url(): string
    {
        return EntryPage::canAccess() ? EntryPage::getUrl() : Filament::getUrl();
    }
}
