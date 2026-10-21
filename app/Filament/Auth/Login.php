<?php

namespace App\Filament\Auth;

use App\Models\Center;
use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Str;

class Login extends BaseLogin
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
            $this->getFormContentComponent(),
            $this->getMultiFactorChallengeFormContentComponent(),
            RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            View::make('filament.auth.demo-login')
                ->viewData(fn () => ['groups' => $this->getDemoUserGroups()])
                ->visible(fn () => config('app.demo_login')),
        ]);
    }

    public function loginAs(int $userId): void
    {
        abort_unless(config('app.demo_login'), 404);

        $user = User::where('email', 'like', '%@' . User::DEMO_DOMAIN)->find($userId);

        if (! $user || ! $this->isUserAllowedToAccessPanel($user)) {
            Notification::make()->title('هذا الحساب غير متاح')->danger()->send();
            return;
        }

        Filament::auth()->login($user);
        session()->regenerate();

        $this->redirectIntended(Filament::getUrl());
    }

    // Groups: admins, then one group per center (its reception first, then its points).
    private function getDemoUserGroups(): array
    {
        $users = User::where('email', 'like', '%@' . User::DEMO_DOMAIN)
            ->where('is_active', true)
            ->with('centers:id')
            ->orderBy('id')
            ->get(['id', 'name', 'role']);

        $groups = [];

        $admins = $users->where('role', User::ROLE_ADMIN);
        if ($admins->isNotEmpty()) {
            $groups['الإدارة'] = $admins->map(fn ($u) => ['id' => $u->id, 'label' => $u->name, 'wide' => true]);
        }

        foreach (Center::where('is_active', true)->orderBy('id')->get() as $center) {
            $short = Str::after($center->name, 'مركز ');

            $members = $users
                ->where('role', '!=', User::ROLE_ADMIN)
                ->filter(fn ($u) => $u->centers->contains('id', $center->id))
                ->sortBy(fn ($u) => [$u->role === User::ROLE_RECEPTION ? 0 : 1, $u->id])
                ->map(fn ($u) => [
                    'id'    => $u->id,
                    'label' => trim(Str::replaceLast($short, '', $u->name)),
                    'wide'  => $u->role === User::ROLE_RECEPTION,
                ]);

            if ($members->isNotEmpty()) {
                $groups[$center->name] = $members;
            }
        }

        return $groups;
    }
}
