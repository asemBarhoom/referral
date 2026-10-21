<x-filament-panels::page>
    <div class="space-y-4 max-w-xl mx-auto" dir="rtl">

        {{-- Center + project badge --}}
        @php
            $center  = $this->getUserCenter();
            $project = $this->getUserProject();
        @endphp

        @if ($center && $project)
            <div class="flex items-center gap-3 text-sm font-medium">
                <span class="inline-block w-3 h-3 rounded-full" style="background: {{ $center->color }};"></span>
                <span class="text-gray-600 dark:text-gray-400">{{ $center->name }}</span>
                <span class="text-gray-300">·</span>
                <span class="font-bold text-gray-800 dark:text-gray-200">{{ $project->code }} — {{ $project->name }}</span>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-300 text-amber-800 rounded-xl p-3 text-sm">
                ⚠️ لم يتم تعيين مركز أو مشروع لحسابك. تواصل مع الأدمن.
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
        </div>

        {{-- Match result --}}
        @if ($matchResult)
            @php
                $code = $matchResult['code'];
                $bene = $matchResult['beneficiary'];

                $colorClass = match($code) {
                    'MATCHED'            => 'bg-green-50 dark:bg-green-950 border-green-400 text-green-800 dark:text-green-200',
                    'PENDING_ENROLLMENT' => 'bg-green-50 dark:bg-green-950 border-green-300 text-green-700 dark:text-green-300',
                    'NOT_REFERRED_TODAY', 'NO_RECEPTION_VISIT' => 'bg-yellow-50 dark:bg-yellow-950 border-yellow-400 text-yellow-800 dark:text-yellow-200',
                    'DUPLICATE_SERVICE'  => 'bg-blue-50 dark:bg-blue-950 border-blue-400 text-blue-800 dark:text-blue-200',
                    default              => 'bg-red-50 dark:bg-red-950 border-red-400 text-red-800 dark:text-red-200',
                };

                $statusText = match($code) {
                    'MATCHED'              => '✅ محوّلة اليوم — يمكن تأكيد الخدمة',
                    'PENDING_ENROLLMENT'   => '🟢 محوّلة — بانتظار اعتماد الارتباط',
                    'NOT_REFERRED_TODAY'   => '🟡 غير محوّلة اليوم — وجّه للاستقبال',
                    'NO_RECEPTION_VISIT'   => '🔴 لم تمر على الاستقبال اليوم',
                    'WRONG_PROJECT'        => '🔴 غير مسجلة في مشروعك',
                    'WRONG_CENTER'         => '🔴 مسجلة في مركز آخر',
                    'BLOCKED'              => '🚫 ممنوعة من الخدمة',
                    'CLOSED_ENROLLMENT'    => '🔴 الارتباط مغلق',
                    'DUPLICATE_SERVICE'    => '🔵 تمت خدمتها اليوم',
                    'UNKNOWN_ID'           => '🔴 غير مسجلة في النظام',
                    'MERGED_ID'            => '🔀 سجل مدموج',
                    'INVALID_ID'           => '❌ رقم هوية غير صالح',
                    default                => $code,
                };
            @endphp

            {{-- Status banner --}}
            <div class="border rounded-xl p-4 {{ $colorClass }}">
                <p class="font-bold text-lg">{{ $statusText }}</p>

                @if ($bene)
                    <div class="mt-2 pt-2 border-t border-current border-opacity-20">
                        <p class="font-semibold text-base">{{ $bene->name }}</p>
                        <p class="text-sm opacity-70">{{ $bene->identity_number }}</p>
                    </div>
                @endif

                @if ($code === 'DUPLICATE_SERVICE' && isset($matchResult['service']))
                    <p class="text-sm mt-1 opacity-80">
                        {{ \Carbon\Carbon::parse($matchResult['service']->service_time)->timezone('Asia/Gaza')->format('H:i') }}
                    </p>
                @endif

                @if ($code === 'BLOCKED' && isset($matchResult['restriction']))
                    <p class="text-sm mt-1">{{ $matchResult['restriction']->reason }}</p>
                @endif
            </div>

            {{-- Confirm service button (MATCHED / PENDING_ENROLLMENT) --}}
            @if (in_array($code, ['MATCHED', 'PENDING_ENROLLMENT']))
                <button
                    wire:click="confirmService"
                    class="w-full bg-green-600 hover:bg-green-700 text-white rounded-xl py-4 text-xl font-bold transition"
                >
                    تأكيد الخدمة
                </button>
            @endif

            {{-- Exceptional service --}}
            @if ($project && $project->allow_exceptional_service && !in_array($code, ['INVALID_ID', 'DUPLICATE_SERVICE', 'BLOCKED', 'MERGED_ID']))
                @if (!$showExceptionalForm)
                    <button
                        wire:click="$set('showExceptionalForm', true)"
                        class="w-full border border-amber-400 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950 rounded-xl py-2 font-medium transition text-sm"
                    >
                        خدمة استثنائية + مراجعة
                    </button>
                @else
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-5 space-y-3">
                        <h3 class="font-semibold text-amber-700 dark:text-amber-300">الخدمة الاستثنائية</h3>

                        <div>
                            <label class="block text-sm mb-1">السبب <span class="text-red-500">*</span></label>
                            <select wire:model="exceptionReasonId" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                                <option value="">-- اختر السبب --</option>
                                @foreach ($this->getExceptionReasons() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('exceptionReasonId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm mb-1">ملاحظة <span class="text-red-500">*</span></label>
                            <textarea wire:model="exceptionNote" rows="2" class="w-full border rounded-lg px-3 py-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white"></textarea>
                            @error('exceptionNote') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-2">
                            <button wire:click="confirmExceptionalService" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg py-2 font-semibold transition">
                                تأكيد الاستثناء
                            </button>
                            <button wire:click="$set('showExceptionalForm', false)" class="px-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                إلغاء
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        @endif
    </div>
</x-filament-panels::page>
