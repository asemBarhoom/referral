@if (auth()->check())
    <div class="brand">
        <img src="{{ asset('images/logo-mark.png') }}" alt="" class="brand-mark">
        <div class="brand-text">
            <span class="brand-name">جمعية أرض الإنسان</span>
            <span class="brand-sub">نظام الإحالة</span>
        </div>
    </div>
@else
    <img src="{{ asset('images/logo.png') }}" alt="جمعية أرض الإنسان" class="brand-full">
@endif

<style>
    .brand { display: flex; align-items: center; gap: .625rem; height: 100%; }
    .brand-mark { height: 100%; width: auto; }
    .brand-text { display: flex; flex-direction: column; line-height: 1.2; white-space: nowrap; }
    .brand-name { font-size: .95rem; font-weight: 700; color: var(--primary-700); }
    .brand-sub { font-size: .7rem; color: var(--gray-500); }
    .dark .brand-name { color: var(--primary-300); }
    .brand-full { height: 100%; width: auto; margin-inline: auto; display: block; }
    .dark .brand-full { background: #fff; border-radius: .75rem; padding: .375rem; box-sizing: border-box; }
</style>
