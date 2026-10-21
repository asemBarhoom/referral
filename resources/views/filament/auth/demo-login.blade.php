<div class="demo-login">
    <div class="demo-login-divider"><span>دخول سريع — مرحلة التجريب</span></div>

    @forelse ($groups as $label => $users)
        <div class="demo-login-group">
            <div class="demo-login-label">{{ $label }}</div>
            <div class="demo-login-grid">
                @foreach ($users as $user)
                    <x-filament::button
                        color="gray"
                        size="sm"
                        :class="($user['wide'] ?? false) ? 'demo-login-wide' : null"
                        wire:click="loginAs({{ $user['id'] }})"
                    >
                        {{ $user['label'] }}
                    </x-filament::button>
                @endforeach
            </div>
        </div>
    @empty
        <p class="demo-login-empty">لا توجد حسابات تجريبية. شغّل DemoUsersSeeder.</p>
    @endforelse
</div>

<style>
    .demo-login { display: flex; flex-direction: column; gap: 1rem; }
    .demo-login-divider { display: flex; align-items: center; gap: .75rem; font-size: .75rem; color: var(--gray-500); }
    .demo-login-divider::before, .demo-login-divider::after { content: ""; flex: 1; height: 1px; background: var(--gray-200); }
    .dark .demo-login-divider::before, .dark .demo-login-divider::after { background: rgb(255 255 255 / .1); }
    .demo-login-group { display: flex; flex-direction: column; gap: .5rem; }
    .demo-login-label { font-size: .8125rem; font-weight: 600; color: var(--gray-700); }
    .dark .demo-login-label { color: var(--gray-300); }
    .demo-login-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .5rem; }
    .demo-login-wide { grid-column: 1 / -1; }
    .demo-login-empty { font-size: .8125rem; color: var(--gray-500); text-align: center; }
</style>
