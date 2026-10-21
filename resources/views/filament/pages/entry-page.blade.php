<x-filament-panels::page>
@php
    $isPoint  = $this->isPoint();
    $center   = $this->center();
    $project  = $this->project();
    $stats    = $this->getTodayStats();
    $projects = $isPoint ? [] : $this->getCenterProjects();
@endphp

<div class="ep" x-data x-on:keydown.escape.window="$wire.resetForm()">
    {{-- Who and where, plus today's numbers --}}
    <div class="ep-bar">
        <div class="ep-badges">
            @if ($center)
                <x-filament::badge :color="$center->color ?? 'gray'" icon="heroicon-m-building-office-2" size="lg">{{ $center->name }}</x-filament::badge>
            @else
                <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">لا يوجد مركز مرتبط بحسابك</x-filament::badge>
            @endif

            @if ($isPoint)
                @if ($project)
                    <x-filament::badge color="primary" icon="heroicon-m-hand-raised" size="lg">{{ $project->name }}</x-filament::badge>
                @else
                    <x-filament::badge color="danger" icon="heroicon-m-exclamation-triangle">لا يوجد مشروع مرتبط بحسابك</x-filament::badge>
                @endif
            @endif
        </div>

        <div class="ep-stats">
            <div><b>{{ $stats['total'] }}</b><span>اليوم</span></div>
            <div><b class="is-new">{{ $stats['newCount'] }}</b><span>جديدة</span></div>
            <div><b>{{ $stats['total'] - $stats['newCount'] }}</b><span>متكررة</span></div>
        </div>
    </div>

    <x-filament::section>
        <div class="ep-form">

            {{-- Search --}}
            <div class="ep-search">
                <x-filament::input.wrapper class="ep-id">
                    <x-filament::input
                        type="text"
                        inputmode="numeric"
                        maxlength="9"
                        dir="ltr"
                        autocomplete="off"
                        placeholder="رقم الهوية"
                        x-init="$nextTick(() => $el.focus())"
                        x-on:entry-saved.window="$nextTick(() => $el.focus())"
                        wire:model="identity_number"
                        wire:keydown.enter="searchBeneficiary"
                    />
                </x-filament::input.wrapper>

                <x-filament::button wire:click="searchBeneficiary" icon="heroicon-m-magnifying-glass" size="lg">
                    بحث
                </x-filament::button>
            </div>

            @if ($searched)
                {{-- Status --}}
                <div class="ep-status">
                    @if (! $found_beneficiary)
                        <x-filament::badge color="info" icon="heroicon-m-sparkles">حالة جديدة — أول مرة</x-filament::badge>
                    @elseif ($today_entry && $isPoint)
                        <x-filament::badge color="warning" icon="heroicon-m-check-circle">
                            خُدمت اليوم {{ $today_entry->entry_time?->format('H:i') }}
                        </x-filament::badge>
                        <span class="ep-meta">سجّلها {{ $today_entry->staff?->name }}</span>
                    @elseif ($today_entry)
                        <x-filament::badge color="warning" icon="heroicon-m-arrows-right-left">
                            محوّلة اليوم {{ $today_entry->entry_time?->format('H:i') }}
                        </x-filament::badge>
                        <span class="ep-meta">عدّل المشاريع ثم اضغط تحديث التحويل</span>
                    @else
                        @php
                            $last = $found_beneficiary->entries()->with('center')->latest('entry_time')->first();
                        @endphp
                        <x-filament::badge color="success" icon="heroicon-m-check-badge">حالة مسجلة</x-filament::badge>
                        @if ($last)
                            <span class="ep-meta" title="{{ $last->entry_time?->format('d/m/Y H:i') }}">
                                آخر زيارة {{ $last->entry_time?->diffForHumans() }} · {{ $last->center?->name }}
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Reception: who transferred today's referral --}}
                @if ($lastTransfer = $this->getLastTransfer())
                    <div class="ep-note">
                        <x-filament::icon icon="heroicon-m-information-circle" class="ep-note-icon" />
                        <span>
                            حوّلها {{ $lastTransfer['by'] }}
                            @if ($lastTransfer['from']) من {{ $lastTransfer['from'] }} @endif
                            إلى {{ $lastTransfer['to'] }} الساعة {{ $lastTransfer['at'] }}
                        </span>
                    </div>
                @endif

                {{-- Point: where reception sent the case today --}}
                @if ($isPoint && $found_beneficiary)
                    @php $referredTo = $this->getTodayReferralProjects(); @endphp
                    <div class="ep-note">
                        <x-filament::icon icon="heroicon-m-arrow-turn-down-left" class="ep-note-icon" />
                        @if ($referredTo)
                            <span>محوّلة اليوم إلى:</span>
                            @foreach ($referredTo as $pname)
                                <x-filament::badge color="gray">{{ $pname }}</x-filament::badge>
                            @endforeach
                        @else
                            <span>لا يوجد تحويل من الاستقبال اليوم</span>
                        @endif
                    </div>
                @endif

                {{-- Name --}}
                <div class="ep-field">
                    <label for="ep-name">الاسم <small>(اختياري)</small></label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            id="ep-name"
                            type="text"
                            placeholder="اسم المستفيد"
                            wire:model="name"
                            wire:keydown.enter="submit"
                        />
                    </x-filament::input.wrapper>
                </div>

                {{-- Reception: projects --}}
                @unless ($isPoint)
                    <div class="ep-field">
                        <label>المشاريع</label>
                        @if (count($projects))
                            <div class="ep-projects">
                                @foreach ($projects as $id => $pname)
                                    <label class="ep-project" wire:key="project-{{ $id }}">
                                        <x-filament::input.checkbox value="{{ $id }}" wire:model="selected_projects" />
                                        <span>{{ $pname }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="ep-meta">لا توجد مشاريع مفعّلة لهذا المركز.</p>
                        @endif
                    </div>
                @endunless

                {{-- Actions --}}
                <div class="ep-actions">
                    @if ($isPoint)
                        @unless ($today_entry)
                            <x-filament::button wire:click="submit" icon="heroicon-m-check" size="xl" class="ep-submit">
                                تم تقديم الخدمة
                            </x-filament::button>
                        @endunless
                        {{ $this->transferAction }}
                    @else
                        <x-filament::button wire:click="submit" icon="heroicon-m-check" size="xl" class="ep-submit">
                            {{ $today_entry ? 'تحديث التحويل' : 'حفظ التحويل' }}
                        </x-filament::button>
                    @endif
                    <x-filament::button wire:click="resetForm" color="gray" size="xl">
                        إلغاء
                    </x-filament::button>
                </div>
            @endif

        </div>
    </x-filament::section>
</div>

<style>
    .ep { max-width: 44rem; margin-inline: auto; display: flex; flex-direction: column; gap: 1rem; }

    .ep-bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; }
    .ep-badges { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .ep-stats { display: flex; gap: .5rem; }
    .ep-stats > div {
        display: flex; align-items: baseline; gap: .375rem;
        padding: .4rem .875rem; border-radius: .5rem;
        background: #fff; box-shadow: 0 0 0 1px rgb(0 0 0 / .06);
    }
    .ep-stats b { font-size: 1.125rem; font-weight: 700; color: var(--gray-950); font-variant-numeric: tabular-nums; }
    .ep-stats b.is-new { color: var(--success-600); }
    .ep-stats span { font-size: .75rem; color: var(--gray-500); }

    .ep-form { display: flex; flex-direction: column; gap: 1.25rem; }

    .ep-search { display: flex; gap: .75rem; }
    .ep-search .ep-id { flex: 1; }
    .ep-id input { font-size: 1.25rem; line-height: 2; text-align: center; font-variant-numeric: tabular-nums; }
    .ep-id input:not(:placeholder-shown) { letter-spacing: .2em; }

    .ep-status {
        display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
        padding-top: 1.25rem; border-top: 1px solid var(--gray-100);
    }
    .ep-meta { font-size: .8125rem; color: var(--gray-500); }
    .ep-note { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem; font-size: .8125rem; color: var(--gray-500); }
    .ep-note-icon { width: 1rem; height: 1rem; color: var(--gray-400); }

    .ep-field { display: flex; flex-direction: column; gap: .5rem; }
    .ep-field > label { font-size: .875rem; font-weight: 500; color: var(--gray-950); }
    .ep-field small { font-weight: 400; color: var(--gray-400); }

    .ep-projects { display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: .5rem; }
    .ep-project {
        display: flex; align-items: center; gap: .625rem;
        padding: .625rem .75rem; border-radius: .5rem; cursor: pointer;
        border: 1px solid var(--gray-200); font-size: .875rem; color: var(--gray-700);
        transition: border-color .15s, background-color .15s;
    }
    .ep-project:hover { border-color: var(--gray-300); }
    .ep-project:has(:checked) {
        border-color: var(--primary-500); color: var(--gray-950); font-weight: 500;
        background: color-mix(in oklab, var(--primary-500) 8%, transparent);
    }

    .ep-actions { display: flex; gap: .75rem; flex-wrap: wrap; }
    .ep-submit { flex: 1; }

    .dark .ep-stats > div { background: var(--gray-900); box-shadow: 0 0 0 1px rgb(255 255 255 / .1); }
    .dark .ep-stats b, .dark .ep-field > label, .dark .ep-project:has(:checked) { color: #fff; }
    .dark .ep-status { border-color: rgb(255 255 255 / .1); }
    .dark .ep-project { border-color: rgb(255 255 255 / .1); color: var(--gray-300); }
    .dark .ep-project:hover { border-color: rgb(255 255 255 / .2); }

    @media (max-width: 640px) {
        .ep-bar { flex-direction: column; align-items: stretch; }
        .ep-stats > div { flex: 1; justify-content: center; }
    }
</style>
</x-filament-panels::page>
