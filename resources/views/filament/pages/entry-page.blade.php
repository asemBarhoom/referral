<x-filament-panels::page>
    <div class="max-w-2xl mx-auto space-y-6" dir="rtl">

        {{-- Center badge --}}
        @php
            $center = auth()->user()?->centers()->first();
            $centerColor = $center?->color ?? 'gray';
            $colorMap = ['blue' => 'bg-blue-100 text-blue-800', 'green' => 'bg-green-100 text-green-800', 'orange' => 'bg-orange-100 text-orange-800', 'red' => 'bg-red-100 text-red-800', 'purple' => 'bg-purple-100 text-purple-800', 'gray' => 'bg-gray-100 text-gray-800'];
            $badgeClass = $colorMap[$centerColor] ?? $colorMap['gray'];
        @endphp

        @if($center)
        <div class="flex items-center gap-3">
            <span class="px-4 py-1.5 rounded-full text-sm font-bold {{ $badgeClass }}">
                {{ $center->name }}
            </span>
            <span class="text-sm text-gray-500">
                {{ auth()->user()->name }} · {{ auth()->user()->role === 'reception' ? 'استقبال' : (auth()->user()->role === 'point' ? 'نقطة' : 'أدمن') }}
            </span>
        </div>
        @endif

        {{-- Hint text --}}
        @php $hint = \App\Models\Setting::where('key', auth()->user()->role === 'reception' ? 'hint_reception' : 'hint_point')->value('value'); @endphp
        @if($hint)
        <p class="text-sm text-gray-500 italic border-r-4 border-gray-200 pr-3">{{ $hint }}</p>
        @endif

        {{-- Search form --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
            <div class="flex gap-3">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input
                        type="text"
                        wire:model.defer="identity_number"
                        wire:keydown.enter="searchBeneficiary"
                        placeholder="رقم الهوية (9 أرقام)"
                        dir="ltr"
                        class="text-lg font-mono"
                        maxlength="9"
                    />
                </x-filament::input.wrapper>
                <x-filament::button wire:click="searchBeneficiary" color="gray">
                    بحث
                </x-filament::button>
            </div>

            @if($searched)
                {{-- Status badge --}}
                @if($found_beneficiary)
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
                        <span class="text-sm font-medium text-green-700 dark:text-green-400">
                            حالة مسجلة
                            @if($found_beneficiary->name) — {{ $found_beneficiary->name }} @endif
                        </span>
                        @if($today_entry)
                            <span class="text-xs text-orange-600 mr-2">
                                🟠 أُدخلت اليوم {{ $today_entry->entry_time?->setTimezone('Asia/Gaza')->format('H:i') }}
                            </span>
                        @endif
                    </div>

                    {{-- Last visit info --}}
                    @php
                        $lastEntry = $found_beneficiary->entries()->with(['projects','center','staff'])->latest('entry_time')->first();
                    @endphp
                    @if($lastEntry && !$today_entry)
                    <div class="text-xs text-gray-500 bg-gray-50 dark:bg-gray-700 rounded p-2">
                        آخر زيارة:
                        {{ $lastEntry->entry_time?->setTimezone('Asia/Gaza')->format('d/m/Y H:i') }} ·
                        {{ $lastEntry->center?->name }} ·
                        {{ $lastEntry->projects->pluck('name')->join('، ') }} ·
                        بواسطة {{ $lastEntry->staff?->name }}
                    </div>
                    @endif
                @else
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-400">حالة جديدة — أول مرة</span>
                    </div>
                @endif

                {{-- Name field (optional) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم (اختياري)</label>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model.defer="name"
                            placeholder="اسم المستفيد — اختياري"
                        />
                    </x-filament::input.wrapper>
                </div>

                {{-- Projects checkboxes --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">المشاريع</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($this->getAvailableProjects() as $id => $pname)
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg border {{ in_array($id, $selected_projects) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input
                                type="checkbox"
                                value="{{ $id }}"
                                wire:model.live="selected_projects"
                                class="rounded border-gray-300"
                            >
                            <span class="text-sm font-medium">{{ $pname }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Submit --}}
                <x-filament::button wire:click="submit" size="lg" class="w-full">
                    {{ $today_entry ? 'تحديث الإدخال' : 'إدخال' }}
                </x-filament::button>
            @endif
        </div>

        {{-- Today's stats --}}
        @php $stats = $this->getTodayStats(); @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 flex items-center gap-6">
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-600">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">إجمالي اليوم</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-semibold text-blue-500">{{ $stats['newCount'] }}</div>
                <div class="text-xs text-gray-500 mt-1">جديدة</div>
            </div>
            <div class="text-center">
                <div class="text-xl font-semibold text-gray-500">{{ $stats['total'] - $stats['newCount'] }}</div>
                <div class="text-xs text-gray-500 mt-1">متكررة</div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
