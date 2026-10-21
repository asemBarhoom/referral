<x-filament-panels::page>
@php
    $user   = auth()->user();
    $center = $user?->centers()->first();

    $colorHexMap = [
        'blue'   => '#2563eb',
        'green'  => '#059669',
        'orange' => '#ea580c',
        'red'    => '#dc2626',
        'purple' => '#7c3aed',
        'gray'   => '#4b5563',
    ];
    $accent = $colorHexMap[$center?->color ?? 'gray'];

    $role = $user?->role;
    $roleLabel = match($role) {
        'reception' => 'استقبال',
        'point'     => 'نقطة مشروع',
        'admin'     => 'مدير النظام',
        default     => $role ?? '',
    };

    $hintKey = $role === 'reception' ? 'hint_reception' : 'hint_point';
    $hint = \App\Models\Setting::where('key', $hintKey)->value('value');

    $stats    = $this->getTodayStats();
    $projects = $this->getAvailableProjects();
@endphp

<style>
    /* hide Filament page header for this page */
    .fi-header { display: none !important; }
    .fi-page-content { padding: 0 !important; }
    .fi-page-main { padding: 0 !important; }
    .fi-page-header-main-ctn { padding-bottom: 0 !important; }

    .ep-accent-bg { background-color: {{ $accent }}; }
    .ep-accent-text { color: {{ $accent }}; }
    .ep-accent-ring:focus { outline: none; box-shadow: 0 0 0 3px {{ $accent }}33; border-color: {{ $accent }}; }
    .ep-accent-border { border-color: {{ $accent }}; }

    /* project card selected state */
    .ep-proj-card.selected { background-color: {{ $accent }}; border-color: {{ $accent }}; color: white; }
    .ep-proj-card.selected .ep-check { border-color: white; background-color: rgba(255,255,255,0.25); }
    .ep-proj-card.selected .ep-check svg { display: block; }
    .ep-proj-card:not(.selected) .ep-check svg { display: none; }

    /* stat number */
    .ep-stat-total { color: {{ $accent }}; }
</style>

<div class="ep-root" dir="rtl" style="min-height:calc(100vh - 4rem); background:#f9fafb;">

    {{-- ── Top Bar ──────────────────────────────────────────────── --}}
    <div class="ep-accent-bg text-white px-5 py-3 flex items-center justify-between shadow-md">

        {{-- User --}}
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-base shrink-0"
                 style="background:rgba(255,255,255,.2)">
                {{ mb_substr($user?->name ?? '?', 0, 1) }}
            </div>
            <div class="leading-tight">
                <div class="font-semibold text-sm">{{ $user?->name }}</div>
                <div class="text-xs" style="color:rgba(255,255,255,.75)">{{ $roleLabel }}</div>
            </div>
        </div>

        {{-- Center badge --}}
        @if($center)
        <div class="flex items-center gap-2 rounded-full px-4 py-1.5"
             style="background:rgba(255,255,255,.15)">
            <div class="w-2 h-2 rounded-full bg-white" style="animation:pulse 2s infinite"></div>
            <span class="font-bold text-sm tracking-wide">{{ $center->name }}</span>
        </div>
        @endif

        {{-- Live clock --}}
        <div class="text-left text-xs leading-tight hidden sm:block" style="color:rgba(255,255,255,.8)">
            <div id="ep-clock" class="font-mono text-base font-semibold text-white"></div>
            <div>{{ now('Asia/Gaza')->isoFormat('dddd، D MMMM') }}</div>
        </div>
    </div>

    {{-- ── Page Body ────────────────────────────────────────────── --}}
    <div style="max-width:42rem; margin:0 auto; padding:1.5rem 1rem; display:flex; flex-direction:column; gap:1.25rem;">

        {{-- Hint --}}
        @if($hint)
        <div class="flex items-start gap-3 text-sm rounded-xl px-4 py-3"
             style="background:#fff; border:1px solid #e5e7eb; color:#6b7280">
            <svg class="shrink-0 mt-0.5" style="width:1rem;height:1rem;color:#9ca3af" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $hint }}</span>
        </div>
        @endif

        {{-- ── Main Card ────────────────────────────────────────── --}}
        <div style="background:#fff; border-radius:1rem; border:1px solid #e5e7eb; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,.06); display:flex; flex-direction:column; gap:1rem;">

            {{-- Identity input row --}}
            <div style="display:flex; gap:.75rem;">
                <input
                    type="text"
                    inputmode="numeric"
                    wire:model.defer="identity_number"
                    wire:keydown.enter="searchBeneficiary"
                    placeholder="رقم الهوية — 9 أرقام"
                    maxlength="9"
                    autofocus
                    dir="ltr"
                    class="ep-accent-ring"
                    style="flex:1; border-radius:.75rem; border:1.5px solid #d1d5db; background:#f9fafb; font-family:monospace; font-size:1.25rem; text-align:center; letter-spacing:.15em; padding:.875rem 1rem; transition:border-color .15s,box-shadow .15s; color:#111827;"
                >
                <button
                    wire:click="searchBeneficiary"
                    wire:loading.attr="disabled"
                    class="ep-accent-bg"
                    style="color:#fff; font-weight:600; border-radius:.75rem; padding:.875rem 1.5rem; border:none; cursor:pointer; white-space:nowrap; transition:opacity .15s; display:flex; align-items:center; gap:.5rem; font-size:.9rem;"
                    onmouseenter="this.style.opacity='.88'" onmouseleave="this.style.opacity='1'"
                >
                    <span wire:loading.remove wire:target="searchBeneficiary">بحث</span>
                    <span wire:loading wire:target="searchBeneficiary" style="display:inline-flex;align-items:center;gap:.375rem">
                        <svg style="width:1rem;height:1rem;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        جاري...
                    </span>
                </button>
            </div>

            @if($searched)

            {{-- Separator --}}
            <div style="height:1px; background:#f3f4f6;"></div>

            {{-- Status + name row --}}
            <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
                @if($found_beneficiary)
                    @if($today_entry)
                        <span style="display:inline-flex;align-items:center;gap:.4rem;background:#fffbeb;color:#b45309;border:1px solid #fcd34d;font-size:.75rem;font-weight:700;padding:.35rem .85rem;border-radius:9999px;">
                            <svg style="width:.875rem;height:.875rem" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                            أُدخلت اليوم {{ $today_entry->entry_time?->setTimezone('Asia/Gaza')->format('H:i') }}
                        </span>
                    @else
                        <span style="display:inline-flex;align-items:center;gap:.4rem;background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;font-size:.75rem;font-weight:700;padding:.35rem .85rem;border-radius:9999px;">
                            <svg style="width:.875rem;height:.875rem" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            حالة مسجلة
                        </span>
                    @endif
                    @if($found_beneficiary->name)
                        <span style="font-size:.875rem;font-weight:600;color:#374151;">{{ $found_beneficiary->name }}</span>
                    @endif
                    <span style="font-family:monospace;font-size:.75rem;color:#9ca3af;margin-right:auto;">{{ $found_beneficiary->identity_number }}</span>
                @else
                    <span style="display:inline-flex;align-items:center;gap:.4rem;background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;font-size:.75rem;font-weight:700;padding:.35rem .85rem;border-radius:9999px;">
                        <svg style="width:.875rem;height:.875rem" fill="currentColor" viewBox="0 0 20 20"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z"/></svg>
                        حالة جديدة — أول مرة
                    </span>
                @endif
            </div>

            {{-- Last visit info --}}
            @if($found_beneficiary && !$today_entry)
            @php
                $lastEntry = $found_beneficiary->entries()->with(['projects','center','staff'])->latest('entry_time')->first();
            @endphp
            @if($lastEntry)
            <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:.75rem;padding:.75rem 1rem;font-size:.75rem;color:#6b7280;display:flex;flex-wrap:wrap;gap:.25rem .75rem;align-items:center;">
                <span style="font-weight:600;color:#374151;">آخر زيارة:</span>
                <span>{{ $lastEntry->entry_time?->setTimezone('Asia/Gaza')->format('d/m/Y — H:i') }}</span>
                <span>{{ $lastEntry->center?->name }}</span>
                <span class="ep-accent-text" style="font-weight:600;">{{ $lastEntry->projects->pluck('name')->join('، ') }}</span>
            </div>
            @endif
            @endif

            {{-- Name field --}}
            <div>
                <label style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em;">
                    الاسم <span style="font-weight:400;text-transform:none;color:#9ca3af;">(اختياري)</span>
                </label>
                <input
                    type="text"
                    wire:model.defer="name"
                    placeholder="اسم المستفيد"
                    class="ep-accent-ring"
                    style="width:100%;border-radius:.75rem;border:1.5px solid #e5e7eb;background:#f9fafb;color:#111827;padding:.625rem 1rem;font-size:.875rem;box-sizing:border-box;transition:border-color .15s,box-shadow .15s;"
                >
            </div>

            {{-- Projects grid --}}
            @if(count($projects))
            <div>
                <label style="display:block;font-size:.7rem;font-weight:700;color:#6b7280;margin-bottom:.625rem;text-transform:uppercase;letter-spacing:.05em;">
                    المشاريع <span style="font-weight:400;text-transform:none;color:#9ca3af;">(اختر واحداً أو أكثر)</span>
                </label>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem;">
                    @foreach($projects as $id => $pname)
                    @php $sel = in_array($id, $selected_projects); @endphp
                    <label class="ep-proj-card {{ $sel ? 'selected' : '' }}"
                           style="display:flex;align-items:center;gap:.625rem;cursor:pointer;border-radius:.75rem;border:2px solid {{ $sel ? $accent : '#e5e7eb' }};padding:.75rem;transition:border-color .15s,background .15s;user-select:none;background:{{ $sel ? $accent : '#fff' }};color:{{ $sel ? '#fff' : '#374151' }};">
                        <input type="checkbox" value="{{ $id }}" wire:model.live="selected_projects" style="position:absolute;opacity:0;width:0;height:0;">
                        <div class="ep-check" style="width:1rem;height:1rem;border-radius:.25rem;border:2px solid {{ $sel ? 'rgba(255,255,255,.6)' : '#d1d5db' }};display:flex;align-items:center;justify-content:center;background:{{ $sel ? 'rgba(255,255,255,.2)' : 'transparent' }};flex-shrink:0;">
                            @if($sel)
                            <svg style="width:.6rem;height:.6rem;color:white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </div>
                        <span style="font-size:.875rem;font-weight:500;line-height:1.25;">{{ $pname }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Submit button --}}
            <button
                wire:click="submit"
                wire:loading.attr="disabled"
                class="ep-accent-bg"
                style="width:100%;color:#fff;font-weight:700;font-size:1rem;border-radius:.75rem;padding:.875rem;border:none;cursor:pointer;transition:opacity .15s;display:flex;align-items:center;justify-content:center;gap:.5rem;box-shadow:0 1px 3px rgba(0,0,0,.12);"
                onmouseenter="this.style.opacity='.88'" onmouseleave="this.style.opacity='1'"
            >
                <span wire:loading.remove wire:target="submit">
                    {{ $today_entry ? 'تحديث الإدخال' : 'إدخال' }}
                </span>
                <span wire:loading wire:target="submit" style="display:inline-flex;align-items:center;gap:.5rem;">
                    <svg style="width:1.25rem;height:1.25rem;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    جاري الحفظ...
                </span>
            </button>

            @endif
        </div>

        {{-- ── Daily Stats ───────────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;">
            <div style="background:#fff;border-radius:1rem;border:1px solid #e5e7eb;padding:1rem;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,.04);">
                <div class="ep-stat-total" style="font-size:2rem;font-weight:900;line-height:1;">{{ $stats['total'] }}</div>
                <div style="font-size:.7rem;color:#9ca3af;margin-top:.25rem;font-weight:600;">إجمالي اليوم</div>
            </div>
            <div style="background:#fff;border-radius:1rem;border:1px solid #e5e7eb;padding:1rem;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,.04);">
                <div style="font-size:2rem;font-weight:900;line-height:1;color:#10b981;">{{ $stats['newCount'] }}</div>
                <div style="font-size:.7rem;color:#9ca3af;margin-top:.25rem;font-weight:600;">جديدة</div>
            </div>
            <div style="background:#fff;border-radius:1rem;border:1px solid #e5e7eb;padding:1rem;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,.04);">
                <div style="font-size:2rem;font-weight:900;line-height:1;color:#9ca3af;">{{ $stats['total'] - $stats['newCount'] }}</div>
                <div style="font-size:.7rem;color:#9ca3af;margin-top:.25rem;font-weight:600;">متكررة</div>
            </div>
        </div>

    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.fi-sidebar ~ .fi-main .fi-page-header-main-ctn > .fi-header { display:none!important; }
</style>

<script>
(function () {
    function tick() {
        const el = document.getElementById('ep-clock');
        if (el) el.textContent = new Date().toLocaleTimeString('ar-EG', {
            timeZone: 'Asia/Gaza', hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
</x-filament-panels::page>
