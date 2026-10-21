<x-filament-panels::page>
    <div class="space-y-4 max-w-2xl mx-auto" dir="rtl">

        {{-- Center badge --}}
        @php $center = $this->getUserCenter(); @endphp
        @if ($center)
            <div class="flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                <span class="inline-block w-3 h-3 rounded-full" style="background: {{ $center->color }};"></span>
                مركز {{ $center->name }}
            </div>
        @else
            <div class="bg-amber-50 border border-amber-300 text-amber-800 rounded-xl p-3 text-sm">
                ⚠️ لم يتم تعيين مركز لحسابك. تواصل مع الأدمن.
            </div>
        @endif

        {{-- Identity input --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-6">
            <label class="block text-base font-semibold mb-3 text-gray-800 dark:text-gray-200">رقم الهوية</label>
            <input
                wire:model.live.debounce.400ms="identityInput"
                type="text"
                inputmode="numeric"
                maxlength="12"
                placeholder="أدخل 9 أرقام..."
                class="w-full border border-gray-300 dark:border-gray-700 rounded-lg px-4 py-3 text-2xl text-center tracking-widest focus:outline-none focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white"
                autofocus
            />
            <p class="text-xs text-gray-400 mt-1 text-center">البحث تلقائي عند اكتمال 9 أرقام صحيحة</p>
        </div>

        {{-- Result section --}}
        @if ($lookupResult)

            {{-- INVALID_ID --}}
            @if ($lookupResult['code'] === 'INVALID_ID')
                <div class="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-xl p-4 text-red-700 dark:text-red-300">
                    ❌ رقم الهوية غير صالح — يجب أن يكون 9 أرقام
                </div>

            {{-- UNKNOWN_ID → new beneficiary form --}}
            @elseif ($lookupResult['code'] === 'UNKNOWN_ID')
                <div class="bg-yellow-50 dark:bg-yellow-950 border border-yellow-300 dark:border-yellow-700 rounded-xl p-4 text-yellow-800 dark:text-yellow-200 text-sm mb-2">
                    🔍 رقم الهوية <strong>{{ $lookupResult['identity_number'] }}</strong> غير مسجل في النظام — أدخل بيانات الحالة الجديدة
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-200">تسجيل حالة جديدة</h3>

                    <div>
                        <label class="block text-sm mb-1">الاسم الكامل <span class="text-red-500">*</span></label>
                        <input wire:model="newName" type="text" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white" placeholder="ثلاثة مقاطع على الأقل" />
                        @error('newName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">رقم الجوال <span class="text-red-500">*</span></label>
                        <input wire:model="newPhone" type="tel" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white" placeholder="05xxxxxxxx" />
                        @error('newPhone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">نوع الهوية <span class="text-red-500">*</span></label>
                        <select wire:model="newIdentityTypeId" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                            <option value="">-- اختر --</option>
                            @foreach ($this->getIdentityTypes() as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('newIdentityTypeId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm mb-1">تاريخ الميلاد (للطفل فقط)</label>
                        <input wire:model="newDob" type="date" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white" />
                    </div>

                    {{-- Project selection --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">المشاريع <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($this->getCenterProjects() as $pid => $pname)
                                <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 dark:border-gray-700">
                                    <input type="checkbox" wire:model="selectedProjectIds" value="{{ $pid }}" class="rounded" />
                                    <span class="text-sm">{{ $pname }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button wire:click="createAndRegister" class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-3 font-semibold transition">
                        تسجيل الحالة وإضافة الزيارة
                    </button>
                </div>

            {{-- MERGED_ID --}}
            @elseif ($lookupResult['code'] === 'MERGED_ID')
                <div class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl p-4 text-gray-700 dark:text-gray-300">
                    🔀 هذا السجل مدموج — يرجى مراجعة الأدمن
                </div>

            {{-- FOUND --}}
            @elseif ($lookupResult['code'] === 'FOUND')
                @php
                    $bene  = $lookupResult['beneficiary'];
                    $block = $lookupResult['block'];
                    $todayVisit = $lookupResult['today_visit'];
                @endphp

                {{-- Block banner --}}
                @if ($block)
                    <div class="bg-red-50 dark:bg-red-950 border border-red-400 rounded-xl p-4 text-red-700 dark:text-red-300 font-medium">
                        🚫 ممنوع من الخدمة —
                        {{ $block->level === 'permanent_block' ? 'منع دائم' : 'منع مؤقت' }}
                        @if($block->reason) : {{ $block->reason }} @endif
                    </div>
                @endif

                {{-- Beneficiary card --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-5 space-y-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $bene->name }}</h2>
                            <p class="text-sm text-gray-500">{{ $bene->identity_number }} · {{ $bene->identityType?->printed_name }}</p>
                            <p class="text-sm text-gray-500">{{ $bene->phone }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full {{ $bene->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $bene->status === 'active' ? 'نشط' : $bene->status }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-400">مركز التسجيل: {{ $bene->registrationCenter?->name }}</p>
                </div>

                {{-- Today visit already registered --}}
                @if ($todayVisit)
                    <div class="bg-blue-50 dark:bg-blue-950 border border-blue-300 dark:border-blue-700 rounded-xl p-4 text-blue-800 dark:text-blue-200">
                        ✅ لديه زيارة اليوم — زيارة رقم {{ $todayVisit->visit_number }}
                        @if ($todayVisit->referrals->count())
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($todayVisit->referrals as $ref)
                                    <li>• {{ $ref->beneficiaryProject?->project?->code }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Allow adding more referrals --}}
                    @if (!$block)
                        <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-5">
                            <h3 class="font-medium text-sm mb-2 text-gray-700 dark:text-gray-300">إضافة تحويل لمشاريع أخرى</h3>
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                @foreach ($this->getCenterProjects() as $pid => $pname)
                                    <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 dark:border-gray-700">
                                        <input type="checkbox" wire:model="selectedProjectIds" value="{{ $pid }}" class="rounded" />
                                        <span class="text-sm">{{ $pname }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button wire:click="registerVisit" class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-2 font-semibold transition text-sm">
                                إضافة التحويلات
                            </button>
                        </div>
                    @endif

                {{-- No visit today → register new visit --}}
                @elseif (!$block)
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-5 space-y-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">تسجيل زيارة</h3>

                        {{-- Active center projects --}}
                        @php
                            $centerProjects = $this->getCenterProjects();
                        @endphp

                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($centerProjects as $pid => $pname)
                                @php
                                    $enrolled = $bene->projects->where('project_id', $pid)->first();
                                    $isClosed = $enrolled && $enrolled->status === 'closed';
                                @endphp
                                <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 dark:border-gray-700 {{ $isClosed ? 'opacity-40 cursor-not-allowed' : '' }}">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedProjectIds"
                                        value="{{ $pid }}"
                                        class="rounded"
                                        {{ $isClosed ? 'disabled' : '' }}
                                    />
                                    <span class="text-sm">{{ $pname }}</span>
                                    @if ($enrolled && $enrolled->status === 'pending_approval')
                                        <span class="text-xs text-amber-600">(بانتظار)</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>

                        <button wire:click="registerVisit" class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-3 font-semibold transition">
                            تسجيل الزيارة والتحويلات
                        </button>
                    </div>
                @endif
            @endif
        @endif
    </div>
</x-filament-panels::page>
