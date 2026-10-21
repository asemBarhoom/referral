<?php

namespace App\Filament\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

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

    private function getDemoUserGroups(): array
    {
        $users = User::where('email', 'like', '%@' . User::DEMO_DOMAIN)
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'name', 'role']);

        return array_filter([
            'الإدارة'   => $users->where('role', User::ROLE_ADMIN),
            'الاستقبال' => $users->where('role', User::ROLE_RECEPTION),
            'النقاط'    => $users->where('role', User::ROLE_POINT),
        ], fn ($group) => $group->isNotEmpty());
    }
}
