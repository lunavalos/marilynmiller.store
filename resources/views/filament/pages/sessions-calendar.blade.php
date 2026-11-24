<x-filament-panels::page>
    <div
        x-data="sessionsCalendarManager({
            events: @js($initialEvents),
            isAdmin: @js($isAdmin),
            eventTypes: @js($eventTypes),
            selectedEventType: @entangle('filterEventType'),
        })"
        x-init="init()"
        class="space-y-4"
    >
        {{-- FullCalendar Demos Style: Segmented Glass Filter & Legend Bar --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-4 sm:p-5 rounded-2xl border border-gray-200/80 dark:border-white/10 shadow-xs">
            {{-- Filter Segmented Capsule Group --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-primary-50 dark:bg-primary-950/60 text-[#3CA7A1]">
                        <x-filament::icon icon="heroicon-m-funnel" class="w-3.5 h-3.5" />
                    </span>
                    <span>Filters</span>
                </div>

                {{-- Segmented Capsule Bar matching fullcalendar.io/demos --}}
                <div class="inline-flex flex-wrap items-center p-1 rounded-2xl bg-gray-100/90 dark:bg-gray-800/90 border border-gray-200/80 dark:border-white/10 shadow-inner backdrop-blur-xs gap-1">
                    <button
                        type="button"
                        @click="filterEventType(null)"
                        :class="selectedEventType === null
                            ? 'bg-white dark:bg-gray-900 text-gray-950 dark:text-white shadow-xs font-semibold border border-black/5 dark:border-white/10 ring-1 ring-black/5'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/5 font-medium border border-transparent'"
                        class="group inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs transition-all duration-150 cursor-pointer select-none"
                    >
                        <span
                            :class="selectedEventType === null ? 'bg-[#3CA7A1] ring-2 ring-[#3CA7A1]/25 scale-110' : 'bg-gray-400 group-hover:bg-gray-600 dark:bg-gray-500 dark:group-hover:bg-gray-300'"
                            class="w-2 h-2 rounded-full transition-all duration-150"
                        ></span>
                        <span>All Sessions</span>
                        <span
                            :class="selectedEventType === null ? 'bg-[#3CA7A1]/15 text-[#2e827d] dark:text-[#5eead4] font-bold' : 'bg-gray-200/80 dark:bg-gray-700/80 text-gray-600 dark:text-gray-400'"
                            class="px-1.5 py-0.5 rounded-full text-[10px] font-mono transition-colors"
                        >
                            {{ $totalSessionsCount ?? count($initialEvents) }}
                        </span>
                    </button>

                    @foreach($eventTypes as $type)
                        <button
                            type="button"
                            @click="filterEventType('{{ $type['id'] }}')"
                            :class="selectedEventType === '{{ $type['id'] }}'
                                ? 'bg-white dark:bg-gray-900 text-gray-950 dark:text-white shadow-xs font-semibold border border-black/5 dark:border-white/10 ring-1 ring-black/5'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-950 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/5 font-medium border border-transparent'"
                            class="group inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs transition-all duration-150 cursor-pointer select-none"
                        >
                            <span
                                :class="selectedEventType === '{{ $type['id'] }}' ? 'bg-[#3CA7A1] ring-2 ring-[#3CA7A1]/25 scale-110' : 'bg-gray-400 group-hover:bg-gray-600 dark:bg-gray-500 dark:group-hover:bg-gray-300'"
                                class="w-2 h-2 rounded-full transition-all duration-150"
                            ></span>
                            <span>{{ $type['label'] }}</span>
                            <span
                                :class="selectedEventType === '{{ $type['id'] }}' ? 'bg-[#3CA7A1]/15 text-[#2e827d] dark:text-[#5eead4] font-bold' : 'bg-gray-200/80 dark:bg-gray-700/80 text-gray-600 dark:text-gray-400'"
                                class="px-1.5 py-0.5 rounded-full text-[10px] font-mono transition-colors"
                            >
                                {{ $type['count'] ?? 0 }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Status Legend & Schedule Session Action --}}
            <div class="flex flex-wrap items-center justify-between sm:justify-end gap-3">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border border-gray-200/80 dark:border-white/10 bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 shadow-2xs backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-[#3CA7A1] ring-2 ring-[#3CA7A1]/20"></span>
                        Available
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border border-gray-200/80 dark:border-white/10 bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 shadow-2xs backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-[#059669] ring-2 ring-[#059669]/20"></span>
                        Booked
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border border-gray-200/80 dark:border-white/10 bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 shadow-2xs backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-[#e11d48] ring-2 ring-[#e11d48]/20"></span>
                        Full
                    </span>
                </div>

                <template x-if="isAdmin">
                    <button
                        type="button"
                        @click="openCreateModal()"
                        style="background-color: #3CA7A1 !important; color: #ffffff !important;"
                        class="btn-calendar-primary inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold shadow-xs hover:shadow transition-all duration-150 cursor-pointer active:scale-[0.98]"
                    >
                        <x-filament::icon icon="heroicon-m-plus" class="w-4 h-4 text-white" />
                        <span class="text-white">Schedule Session</span>
                    </button>
                </template>
            </div>
        </div>

        {{-- FullCalendar Container with Glass Card Styling --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-white/10 p-4 sm:p-6 shadow-xs overflow-hidden">
            <div id="fullcalendar-view" wire:ignore class="min-h-[720px]"></div>
        </div>

        {{-- Google Calendar-Style Modal (Create, Edit & Details) --}}
        <div
            x-show="activeModal"
            x-cloak
            :class="activeModal ? 'pointer-events-auto' : 'pointer-events-none hidden'"
            :style="activeModal ? '' : 'display: none !important;'"
            class="fixed inset-0 z-50 overflow-hidden"
            @keydown.escape.window="closeModal()"
        >
            {{-- Backdrop Overlay with Frosted Blur --}}
            <div
                x-show="activeModal"
                x-transition:enter="ease-out duration-250"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="modal-backdrop-blur"
                @click="closeModal()"
            ></div>

            {{-- Dialog Wrapper --}}
            <div class="fixed inset-0 z-10 flex items-center justify-center p-4 sm:p-6 md:p-8 pointer-events-none">
                <div
                    x-show="activeModal"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="modal-lightbox-dialog bg-white dark:bg-gray-900 rounded-3xl border border-gray-200/90 dark:border-white/10 shadow-2xl max-w-3xl lg:max-w-[840px] w-full text-gray-950 dark:text-white pointer-events-auto"
                >
                {{-- Header (Pinned at top) --}}
                <div class="modal-header-pinned border-b border-gray-100 dark:border-white/10 flex items-center justify-between gap-4 bg-gray-50/70 dark:bg-gray-800/40 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-[#3CA7A1]/15 text-[#2e827d] dark:text-[#5eead4] flex items-center justify-center shrink-0">
                            <x-filament::icon icon="heroicon-m-calendar-days" class="w-5 h-5" />
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold tracking-tight text-gray-950 dark:text-white"
                                x-text="modalMode === 'create' ? 'Schedule New Session' : (modalMode === 'edit' ? 'Edit Session' : selectedEvent?.title)"
                            ></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="modalMode === 'create' ? 'Click on any slot to pick dates, set capacity, and configure recurrence.' : (modalMode === 'edit' ? 'Update class schedule, pricing, capacity, and manage attendees.' : selectedEvent?.event_type_label)"></p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeModal()"
                        class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                        title="Close (Esc)"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                    </button>
                </div>

                {{-- Modal Body: Form for Admin (Create / Edit) --}}
                <template x-if="isAdmin && (modalMode === 'create' || modalMode === 'edit')">
                    <form @submit.prevent="submitForm()" class="modal-form-wrapper">
                        {{-- Scrollable Form Fields --}}
                        <div class="modal-body-scroll px-6 py-6 sm:px-8 sm:py-7 space-y-6">
                        {{-- Row 1: Title & Event Type in 2-column grid --}}
                        <div class="modal-grid-2">
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                    Session / Class Title <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    x-model="form.title"
                                    required
                                    placeholder="e.g. Morning Bootcamp - Group A"
                                    class="modal-input"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                    Event Type <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    x-model="form.event_type"
                                    required
                                    class="modal-input"
                                >
                                    <option value="live_in_person">Live (In-person)</option>
                                    <option value="live_online">Live Online</option>
                                    <option value="recorded">Recorded</option>
                                </select>
                            </div>
                        </div>

                        {{-- Row 2: Date & Time Pickers in 2-column grid --}}
                        <div class="modal-grid-2">
                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                    Start Date & Time <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    x-model="form.start_time"
                                    :min="modalMode === 'create' ? minDateTime : null"
                                    required
                                    class="modal-input font-mono text-xs"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                    End Date & Time <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    x-model="form.end_time"
                                    :min="form.start_time || (modalMode === 'create' ? minDateTime : null)"
                                    required
                                    class="modal-input font-mono text-xs"
                                />
                            </div>
                        </div>

                        {{-- Row 3: Location / Room / Link --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                Location / Gym Studio / Meeting Link
                            </label>
                            <input
                                type="text"
                                x-model="form.location_or_link"
                                placeholder="e.g. Gym Studio 1, Zoom link, or Physical address"
                                class="modal-input"
                            />
                        </div>

                        {{-- Row 4: Capacity & Drop-in Pricing --}}
                        <div class="modal-section-card space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-[#3CA7A1]/15 text-[#2e827d] dark:text-[#5eead4] flex items-center justify-center shrink-0">
                                    <x-filament::icon icon="heroicon-m-ticket" class="w-3.5 h-3.5" />
                                </div>
                                <span>Capacity & Drop-in Single Pass</span>
                            </h4>

                            <div class="modal-grid-2">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                        Max Capacity (Seats / Lugares) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        x-model.number="form.max_seats"
                                        required
                                        placeholder="10"
                                        class="modal-input"
                                    />
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-1">Límite de lugares permitidos en el gimnasio.</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                        Drop-in Price (USD)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm font-semibold pointer-events-none">$</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="form.drop_in_price"
                                            placeholder="25.00"
                                            class="modal-input"
                                            style="padding-left: 2rem !important;"
                                        />
                                    </div>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-1">Precio por sesión individual para no suscritos.</span>
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                    Stripe Drop-in Payment Link
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="url"
                                        x-model="form.stripe_payment_link"
                                        placeholder="https://buy.stripe.com/..."
                                        class="modal-input font-mono text-xs"
                                    />
                                    <template x-if="form.stripe_payment_link">
                                        <button
                                            type="button"
                                            @click="copyLink(form.stripe_payment_link)"
                                            class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shrink-0 flex items-center gap-1.5 transition cursor-pointer"
                                        >
                                            <span x-show="!copiedLink">Copy</span>
                                            <span x-show="copiedLink">Copied!</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Row 5: Recurrence Configuration --}}
                        <div class="modal-section-card space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-2.5 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        x-model="form.is_recurring"
                                        class="w-4 h-4 rounded text-[#3CA7A1] focus:ring-[#3CA7A1] border-gray-300 dark:border-gray-700"
                                    />
                                    <div class="w-6 h-6 rounded-lg bg-[#3CA7A1]/15 text-[#2e827d] dark:text-[#5eead4] flex items-center justify-center shrink-0">
                                        <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" />
                                    </div>
                                    <span>Repeat this session (Sesión recurrente)</span>
                                </label>
                            </div>

                            <div x-show="form.is_recurring" class="modal-grid-2 pt-3 mt-3 border-t border-gray-200/80 dark:border-white/10">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                        Recurrence Pattern (Frecuencia)
                                    </label>
                                    <select
                                        x-model="form.recurrence_rule"
                                        class="modal-input text-xs"
                                    >
                                        <option value="daily">Daily (Diaria)</option>
                                        <option value="weekly">Weekly - Every 7 days (Semanal)</option>
                                        <option value="biweekly">Every 2 Weeks - 14 days (Cada dos semanas)</option>
                                        <option value="every_4_weeks">Every 4 Weeks - 28 days (Cada cuatro semanas)</option>
                                        <option value="monthly">Monthly (Mensual)</option>
                                    </select>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-1">La opción de 4 semanas cuenta exactamente 28 días sin desfase.</span>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                        Repeat Until Date (Límite)
                                    </label>
                                    <input
                                        type="date"
                                        x-model="form.recurrence_end_date"
                                        class="modal-input font-mono text-xs"
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- Row 6: Included Membership Plans --}}
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                Included in Memberships (Planes que incluyen esta sesión gratis)
                            </label>
                            <div class="flex flex-wrap items-center gap-2.5">
                                @foreach($plans as $plan)
                                    <label
                                        :class="(form.plans.includes({{ $plan->id }}) || form.plans.includes('{{ $plan->id }}'))
                                            ? 'bg-[#3CA7A1]/10 border-[#3CA7A1] text-gray-900 dark:text-white font-semibold ring-1 ring-[#3CA7A1]/30 shadow-2xs'
                                            : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60 font-medium'"
                                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-xs cursor-pointer transition select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="{{ $plan->id }}"
                                            x-model="form.plans"
                                            class="rounded text-[#3CA7A1] focus:ring-[#3CA7A1] border-gray-300 dark:border-gray-700"
                                        />
                                        <span>{{ $plan->name_en }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Row 7: If Edit Mode: Show Occupancy & Attendees Section --}}
                        <template x-if="modalMode === 'edit'">
                            <div class="border-t border-gray-200 dark:border-white/10 pt-5 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                        <x-filament::icon icon="heroicon-m-users" class="w-4 h-4 text-[#3CA7A1]" />
                                        Booked Attendees (<span x-text="selectedEvent?.attendees?.length || 0"></span> / <span x-text="form.max_seats"></span> spots)
                                    </h4>
                                    <span
                                        :class="selectedEvent?.is_full ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold'"
                                        x-text="selectedEvent?.is_full ? 'FULL CAPACITY' : selectedEvent?.available_seats + ' spots open'"
                                        class="text-xs"
                                    ></span>
                                </div>

                                {{-- Progress bar --}}
                                <div class="w-full bg-gray-200 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :style="'width: ' + Math.min(100, Math.round(((selectedEvent?.attendees?.length || 0) / (form.max_seats || 1)) * 100)) + '%;'"
                                        :class="selectedEvent?.is_full ? 'bg-rose-500' : 'bg-[#3CA7A1]'"
                                    ></div>
                                </div>

                                {{-- Attendees List --}}
                                <div class="space-y-1.5 max-h-40 overflow-y-auto">
                                    <template x-if="!selectedEvent?.attendees || selectedEvent?.attendees.length === 0">
                                        <p class="text-xs text-gray-400 italic py-1">No clients have reserved this session yet.</p>
                                    </template>
                                    <template x-for="attendee in selectedEvent?.attendees" :key="attendee.id">
                                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-white/5 text-xs">
                                            <div class="space-y-0.5">
                                                <span x-text="attendee.name" class="font-bold text-gray-900 dark:text-gray-100 block"></span>
                                                <span x-text="attendee.email" class="text-[11px] text-gray-500 dark:text-gray-400 block"></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span
                                                    x-text="attendee.status"
                                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300"
                                                ></span>
                                                <button
                                                    type="button"
                                                    @click="removeAttendee(attendee.id)"
                                                    title="Remove from session"
                                                    class="text-gray-400 hover:text-rose-600 p-1.5 rounded-lg transition cursor-pointer"
                                                >
                                                    <x-filament::icon icon="heroicon-m-trash" class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Inline Quick Add Attendee --}}
                                <div class="modal-section-card space-y-2">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300 block">
                                        Manually Add Client
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <select
                                            x-model="newAttendeeId"
                                            class="modal-input text-xs flex-1"
                                        >
                                            <option value="">Select a Client...</option>
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                            @endforeach
                                        </select>
                                        <button
                                            type="button"
                                            @click="addAttendee(form.id)"
                                            :disabled="!newAttendeeId || selectedEvent?.is_full"
                                            style="background-color: #3CA7A1 !important; color: #ffffff !important;"
                                            class="btn-calendar-primary px-4 py-2.5 text-xs font-bold rounded-xl text-white disabled:opacity-50 transition shrink-0 cursor-pointer"
                                        >
                                            + Add Spot
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        </div>

                        {{-- Footer Buttons (Pinned) --}}
                        <div class="modal-footer-pinned border-t border-gray-100 dark:border-white/10 flex flex-wrap items-center justify-between gap-4 bg-gray-50/70 dark:bg-gray-800/40 shrink-0">
                            <div>
                                <template x-if="modalMode === 'edit'">
                                    <div class="flex flex-wrap items-center gap-2">
                                        {{-- If part of recurring series: Show Delete Unique + Delete Series --}}
                                        <template x-if="selectedEvent?.is_part_of_series">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button
                                                    type="button"
                                                    @click="deleteSingleSession(form.id)"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-900/50 transition cursor-pointer"
                                                    title="Delete only this date / session"
                                                >
                                                    <x-filament::icon icon="heroicon-m-calendar" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                                                    <span>Delete Unique</span>
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="deleteSeries(form.id)"
                                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-medium text-gray-500 hover:text-rose-600 dark:text-gray-400 dark:hover:text-rose-400 hover:bg-rose-50/50 dark:hover:bg-rose-950/20 transition cursor-pointer"
                                                    title="Delete all dates in this recurring series"
                                                >
                                                    <x-filament::icon icon="heroicon-m-trash" class="w-3.5 h-3.5" />
                                                    <span>Delete Series</span>
                                                </button>
                                            </div>
                                        </template>

                                        {{-- If standalone single session: Show normal Delete Session --}}
                                        <template x-if="!selectedEvent?.is_part_of_series">
                                            <button
                                                type="button"
                                                @click="deleteSingleSession(form.id)"
                                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                            >
                                                <x-filament::icon icon="heroicon-m-trash" class="w-3.5 h-3.5" />
                                                <span>Delete Session</span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center gap-3 ml-auto">
                                <button
                                    type="button"
                                    @click="closeModal()"
                                    class="px-5 py-2.5 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition cursor-pointer"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    style="background-color: #3CA7A1 !important; color: #ffffff !important;"
                                    class="btn-calendar-primary px-6 py-2.5 rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer active:scale-[0.98]"
                                >
                                    <x-filament::icon icon="heroicon-m-check" class="w-4 h-4 text-white" />
                                    <span class="text-white font-bold" x-text="modalMode === 'create' ? 'Create Session' : 'Save Changes'"></span>
                                </button>
                            </div>
                        </div>
                    </form>
                </template>

                {{-- Modal Body: Subscriber View (Details & Booking) --}}
                <template x-if="!isAdmin || modalMode === 'details'">
                    <div class="modal-form-wrapper">
                        <div class="modal-body-scroll px-6 py-6 sm:px-8 sm:py-7 space-y-6">
                            {{-- Schedule & Location in 2-column grid --}}
                            <div class="modal-grid-2">
                                <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-gray-50/80 dark:bg-gray-800/60 border border-gray-100 dark:border-white/5">
                                    <x-filament::icon icon="heroicon-m-calendar" class="w-5 h-5 text-[#3CA7A1] shrink-0 mt-0.5" />
                                    <div class="space-y-0.5 text-xs">
                                        <span class="font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider text-[10px]">Schedule</span>
                                        <span x-text="selectedEvent?.start_formatted + ' - ' + selectedEvent?.end_formatted" class="font-bold text-gray-900 dark:text-gray-100 text-sm"></span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-gray-50/80 dark:bg-gray-800/60 border border-gray-100 dark:border-white/5">
                                    <x-filament::icon icon="heroicon-m-map-pin" class="w-5 h-5 text-[#3CA7A1] shrink-0 mt-0.5" />
                                    <div class="space-y-0.5 text-xs">
                                        <span class="font-semibold text-gray-500 dark:text-gray-400 block uppercase tracking-wider text-[10px]">Location / Link</span>
                                        <template x-if="selectedEvent?.location_or_link">
                                            <span x-text="selectedEvent?.location_or_link" class="font-bold text-gray-900 dark:text-gray-100 break-all text-sm"></span>
                                        </template>
                                        <template x-if="!selectedEvent?.location_or_link">
                                            <span class="text-gray-400 italic text-sm">In-Person Session (Gym Studio)</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Capacity Bar --}}
                            <div class="modal-section-card space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-semibold flex items-center gap-2 text-gray-800 dark:text-gray-200">
                                        <x-filament::icon icon="heroicon-m-user-group" class="w-4 h-4 text-[#3CA7A1]" />
                                        Gym Spots & Capacity
                                    </span>
                                    <span
                                        :class="selectedEvent?.is_full ? 'text-rose-600 font-bold' : 'text-emerald-600 font-bold'"
                                        x-text="selectedEvent?.is_full ? 'FULL CAPACITY' : selectedEvent?.available_seats + ' of ' + selectedEvent?.max_seats + ' spots open'"
                                    ></span>
                                </div>

                                <div class="w-full bg-gray-200 dark:bg-gray-700 h-2.5 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :style="'width: ' + Math.min(100, Math.round((selectedEvent?.booked_seats / (selectedEvent?.max_seats || 1)) * 100)) + '%;'"
                                        :class="selectedEvent?.is_full ? 'bg-rose-500' : 'bg-[#3CA7A1]'"
                                    ></div>
                                </div>
                            </div>

                            {{-- Drop-in Single Pass --}}
                            <template x-if="selectedEvent?.drop_in_price || selectedEvent?.stripe_payment_link">
                                <div class="modal-section-card modal-section-card-emerald space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                            <x-filament::icon icon="heroicon-m-ticket" class="w-4 h-4" />
                                            Drop-in Single Pass (Pago por sesión)
                                        </span>
                                        <template x-if="selectedEvent?.drop_in_price">
                                            <span x-text="'$' + selectedEvent?.drop_in_price + ' USD'" class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300"></span>
                                        </template>
                                    </div>

                                    <template x-if="selectedEvent?.stripe_payment_link">
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="text"
                                                readonly
                                                :value="selectedEvent?.stripe_payment_link"
                                                class="modal-input font-mono text-xs flex-1"
                                            />
                                            <a
                                                :href="selectedEvent?.stripe_payment_link"
                                                target="_blank"
                                                class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shrink-0 flex items-center gap-1.5 transition"
                                            >
                                                Buy Pass <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="w-3.5 h-3.5" />
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Footer Booking Actions for Subscriber (Pinned) --}}
                        <div class="modal-footer-pinned border-t border-gray-100 dark:border-white/10 flex items-center justify-end gap-3 bg-gray-50/70 dark:bg-gray-800/40 shrink-0">
                            <template x-if="selectedEvent?.is_booked">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 text-white shadow-xs">
                                        <x-filament::icon icon="heroicon-m-check-circle" class="w-4 h-4" />
                                        Spot Reserved ✓
                                    </span>
                                    <template x-if="!selectedEvent?.is_past">
                                        <button
                                            type="button"
                                            @click="cancelBooking(selectedEvent?.session_id)"
                                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-500 hover:text-rose-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
                                        >
                                            Cancel Reservation
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!selectedEvent?.is_booked">
                                <div>
                                    <template x-if="selectedEvent?.is_past">
                                        <button
                                            type="button"
                                            disabled
                                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gray-200 dark:bg-gray-800 text-gray-500 cursor-not-allowed"
                                        >
                                            Past Session (Closed)
                                        </button>
                                    </template>

                                    <template x-if="!selectedEvent?.is_past && selectedEvent?.is_full">
                                        <button
                                            type="button"
                                            disabled
                                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gray-200 dark:bg-gray-800 text-gray-500 cursor-not-allowed"
                                        >
                                            Session Full (0 spots left)
                                        </button>
                                    </template>

                                    <template x-if="!selectedEvent?.is_past && !selectedEvent?.is_full">
                                        <button
                                            type="button"
                                            @click="bookSession(selectedEvent?.session_id)"
                                            style="background-color: #3CA7A1 !important; color: #ffffff !important;"
                                            class="btn-calendar-primary px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer active:scale-[0.98]"
                                        >
                                            <x-filament::icon icon="heroicon-m-calendar-days" class="w-4 h-4 text-white" />
                                            <span class="text-white font-bold">Reserve My Spot</span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <button
                                type="button"
                                @click="closeModal()"
                                class="px-5 py-2.5 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition cursor-pointer"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

    {{-- Load FullCalendar v6 via CDN --}}
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('sessionsCalendarManager', (config) => ({
                    calendar: null,
                    events: config.events || [],
                    isAdmin: config.isAdmin,
                    eventTypes: config.eventTypes || [],
                    selectedEventType: config.selectedEventType,
                    activeModal: false,
                    modalMode: 'create', // 'create' | 'edit' | 'details'
                    selectedEvent: null,
                    newAttendeeId: '',
                    copiedLink: false,

                    form: {
                        id: null,
                        event_type: 'live_in_person',
                        title: '',
                        start_time: '',
                        end_time: '',
                        location_or_link: '',
                        max_seats: 10,
                        drop_in_price: '',
                        stripe_payment_link: '',
                        is_recurring: false,
                        recurrence_rule: 'every_4_weeks',
                        recurrence_end_date: '',
                        plans: [],
                    },

                    init() {
                        const el = document.getElementById('fullcalendar-view');
                        if (!el) return;

                        if (this.calendar) {
                            try { this.calendar.destroy(); } catch (e) {}
                        }

                        const isMobile = window.innerWidth < 768;

                        this.calendar = new FullCalendar.Calendar(el, {
                            initialView: isMobile ? 'timeGridDay' : 'dayGridMonth',
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
                            },
                            buttonText: {
                                today: 'Today',
                                month: 'Month',
                                week: 'Week',
                                day: 'Day',
                                list: 'List',
                            },
                            views: {
                                timeGridWeek: {
                                    slotMinTime: '07:00:00',
                                    slotMaxTime: '22:00:00',
                                    allDaySlot: false,
                                },
                                timeGridDay: {
                                    slotMinTime: '07:00:00',
                                    slotMaxTime: '22:00:00',
                                    allDaySlot: false,
                                },
                            },
                            nowIndicator: true,
                            slotDuration: '00:30:00',
                            slotLabelInterval: '01:00',
                            selectable: true,
                            selectMirror: true,
                            unselectAuto: true,
                            selectAllow: (selectInfo) => {
                                const todayMidnight = new Date();
                                todayMidnight.setHours(0, 0, 0, 0);
                                return selectInfo.start >= todayMidnight;
                            },
                            dayMaxEvents: 3,
                            events: this.events,
                            eventTimeFormat: {
                                hour: 'numeric',
                                minute: '2-digit',
                                meridiem: 'short',
                            },

                            // 1. Click on empty day or empty time slot -> Open Create Session Modal
                            dateClick: (info) => {
                                if (!this.isAdmin) return;
                                this.openCreateModalFromDateClick(info);
                            },

                            select: (info) => {
                                if (!this.isAdmin) return;
                                this.openCreateModalFromSelection(info);
                            },

                            // 2. Click on existing session -> Open Edit Modal (or Details for subscriber)
                            eventClick: (info) => {
                                info.jsEvent.preventDefault();
                                if (this.isAdmin) {
                                    this.openEditModal(info.event.extendedProps);
                                } else {
                                    this.openDetailsModal(info.event.extendedProps);
                                }
                            },

                            eventContent: (arg) => {
                                const props = arg.event.extendedProps;
                                const isFull = props.is_full;
                                const isBooked = props.is_booked;

                                const container = document.createElement('div');
                                container.className = 'fc-custom-event-content p-1 text-xs leading-tight w-full truncate';

                                const header = document.createElement('div');
                                header.className = 'font-bold flex items-center justify-between gap-1';

                                const titleSpan = document.createElement('span');
                                titleSpan.innerText = arg.event.title;
                                titleSpan.className = 'truncate';
                                header.appendChild(titleSpan);

                                if (isBooked) {
                                    const badge = document.createElement('span');
                                    badge.innerText = '✓';
                                    badge.className = 'px-1 rounded bg-emerald-800 text-white text-[10px] shrink-0 font-mono';
                                    header.appendChild(badge);
                                } else if (isFull) {
                                    const badge = document.createElement('span');
                                    badge.innerText = 'FULL';
                                    badge.className = 'px-1 rounded bg-rose-800 text-white text-[9px] shrink-0 font-bold';
                                    header.appendChild(badge);
                                }

                                container.appendChild(header);

                                const sub = document.createElement('div');
                                sub.className = 'text-[11px] opacity-90 flex items-center justify-between gap-1 mt-0.5';

                                const timeSpan = document.createElement('span');
                                timeSpan.innerText = arg.timeText || '';
                                sub.appendChild(timeSpan);

                                const seatSpan = document.createElement('span');
                                seatSpan.innerText = isFull ? '0 left' : (props.available_seats + '/' + props.max_seats + ' spots');
                                seatSpan.className = 'font-medium text-[10px] opacity-95';
                                sub.appendChild(seatSpan);

                                container.appendChild(sub);

                                return { domNodes: [container] };
                            },
                        });

                        this.calendar.render();

                        // Listen for Livewire refresh event safely (FullCalendar v6 compatible)
                        window.addEventListener('calendar-refresh', (event) => {
                            const newEvents = event.detail?.events
                                || (Array.isArray(event.detail) && event.detail[0]?.events)
                                || (Array.isArray(event.detail) ? event.detail : null);

                            if (this.calendar && Array.isArray(newEvents)) {
                                try {
                                    this.calendar.getEventSources().forEach(src => src.remove());
                                } catch (e) {}
                                try {
                                    this.calendar.getEvents().forEach(e => e.remove());
                                } catch (e) {}
                                try {
                                    this.calendar.addEventSource(newEvents);
                                } catch (e) {}

                                // Refresh selectedEvent if modal is open
                                if (this.selectedEvent && this.selectedEvent.session_id) {
                                    const updated = newEvents.find(
                                        (e) => e.extendedProps && e.extendedProps.session_id === this.selectedEvent.session_id
                                    );
                                    if (updated) {
                                        this.selectedEvent = updated.extendedProps;
                                    }
                                }
                            }
                        });

                        // Listen for header Schedule Session action
                        window.addEventListener('calendar-open-create-modal', () => {
                            if (this.isAdmin) {
                                this.openCreateModal();
                            }
                        });
                    },

                    formatDateTimeLocal(date) {
                        const pad = (n) => String(n).padStart(2, '0');
                        const y = date.getFullYear();
                        const m = pad(date.getMonth() + 1);
                        const d = pad(date.getDate());
                        const h = pad(date.getHours());
                        const min = pad(date.getMinutes());
                        return `${y}-${m}-${d}T${h}:${min}`;
                    },

                    get minDateTime() {
                        const now = new Date();
                        const pad = (n) => String(n).padStart(2, '0');
                        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T00:00`;
                    },

                    openCreateModalFromDateClick(info) {
                        if (this.calendar) {
                            try { this.calendar.unselect(); } catch (e) {}
                        }
                        if (this.activeModal) return;
                        let startDate = new Date(info.date);
                        let endDate = new Date(startDate);
                        endDate.setHours(startDate.getHours() + 1);
                        this.openCreateModalForDate(startDate, endDate, info.allDay);
                    },

                    openCreateModalFromSelection(info) {
                        if (this.calendar) {
                            try { this.calendar.unselect(); } catch (e) {}
                        }
                        if (this.activeModal) return;
                        this.openCreateModalForDate(new Date(info.start), new Date(info.end), info.allDay);
                    },

                    openCreateModalForDate(startDate, endDate, allDay = true) {
                        const todayMidnight = new Date();
                        todayMidnight.setHours(0, 0, 0, 0);

                        if (startDate < todayMidnight) {
                            if (this.calendar) {
                                try { this.calendar.unselect(); } catch (e) {}
                            }
                            return;
                        }

                        if (allDay) {
                            startDate.setHours(9, 0, 0, 0);
                            endDate = new Date(startDate);
                            endDate.setHours(10, 0, 0, 0);
                        }

                        let recurrenceEnd = new Date(startDate);
                        recurrenceEnd.setMonth(recurrenceEnd.getMonth() + 3);

                        const pad = (n) => String(n).padStart(2, '0');
                        const defaultRecurrenceEnd = `${recurrenceEnd.getFullYear()}-${pad(recurrenceEnd.getMonth() + 1)}-${pad(recurrenceEnd.getDate())}`;

                        this.form = {
                            id: null,
                            event_type: 'live_in_person',
                            title: '',
                            start_time: this.formatDateTimeLocal(startDate),
                            end_time: this.formatDateTimeLocal(endDate),
                            location_or_link: '',
                            max_seats: 10,
                            drop_in_price: '',
                            stripe_payment_link: '',
                            is_recurring: false,
                            recurrence_rule: 'every_4_weeks',
                            recurrence_end_date: defaultRecurrenceEnd,
                            plans: [],
                        };

                        this.modalMode = 'create';
                        this.activeModal = true;
                        if (this.calendar) {
                            try { this.calendar.unselect(); } catch (e) {}
                        }
                    },

                    openCreateModal() {
                        if (!this.isAdmin) return;

                        let startDate = new Date();
                        startDate.setHours(startDate.getHours() + 1, 0, 0, 0);
                        let endDate = new Date(startDate);
                        endDate.setHours(startDate.getHours() + 1);

                        let recurrenceEnd = new Date(startDate);
                        recurrenceEnd.setMonth(recurrenceEnd.getMonth() + 3);

                        const pad = (n) => String(n).padStart(2, '0');
                        const defaultRecurrenceEnd = `${recurrenceEnd.getFullYear()}-${pad(recurrenceEnd.getMonth() + 1)}-${pad(recurrenceEnd.getDate())}`;

                        this.form = {
                            id: null,
                            event_type: 'live_in_person',
                            title: '',
                            start_time: this.formatDateTimeLocal(startDate),
                            end_time: this.formatDateTimeLocal(endDate),
                            location_or_link: '',
                            max_seats: 10,
                            drop_in_price: '',
                            stripe_payment_link: '',
                            is_recurring: false,
                            recurrence_rule: 'every_4_weeks',
                            recurrence_end_date: defaultRecurrenceEnd,
                            plans: [],
                        };

                        this.modalMode = 'create';
                        this.activeModal = true;
                    },

                    openEditModal(props) {
                        this.selectedEvent = props;

                        this.form = {
                            id: props.session_id,
                            event_type: props.event_type || 'live_in_person',
                            title: props.title,
                            start_time: props.start_time_iso,
                            end_time: props.end_time_iso,
                            location_or_link: props.location_or_link || '',
                            max_seats: props.max_seats || 10,
                            drop_in_price: props.drop_in_price || '',
                            stripe_payment_link: props.stripe_payment_link || '',
                            is_recurring: props.is_recurring,
                            recurrence_rule: props.recurrence_rule_raw || 'every_4_weeks',
                            recurrence_end_date: props.recurrence_end_date_raw || '',
                            plans: props.plan_ids ? [...props.plan_ids] : [],
                        };

                        this.copiedLink = false;
                        this.newAttendeeId = '';
                        this.modalMode = 'edit';
                        this.activeModal = true;
                    },

                    openDetailsModal(props) {
                        this.selectedEvent = props;
                        this.modalMode = 'details';
                        this.activeModal = true;
                    },

                    submitForm() {
                        const payload = JSON.parse(JSON.stringify(this.form));
                        this.closeModal();
                        this.$wire.saveSession(payload).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error saving session:', err);
                        });
                    },

                    filterEventType(type) {
                        this.selectedEventType = type;
                        this.$wire.filterByEventType(type);
                    },

                    closeModal() {
                        this.activeModal = false;
                        this.newAttendeeId = '';
                        this.copiedLink = false;
                        if (this.calendar) {
                            try {
                                this.calendar.unselect();
                            } catch (e) {}
                        }
                    },

                    copyLink(url) {
                        if (!url) return;
                        navigator.clipboard.writeText(url).then(() => {
                            this.copiedLink = true;
                            setTimeout(() => {
                                this.copiedLink = false;
                            }, 2500);
                        });
                    },

                    bookSession(id) {
                        this.closeModal();
                        this.$wire.bookSession(id).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error booking session:', err);
                        });
                    },

                    cancelBooking(id) {
                        this.closeModal();
                        this.$wire.cancelBooking(id).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error cancelling booking:', err);
                        });
                    },

                    addAttendee(sessionId) {
                        if (!this.newAttendeeId || !sessionId) return;
                        const attendeeId = this.newAttendeeId;
                        this.newAttendeeId = '';
                        this.$wire.addAttendee(sessionId, attendeeId).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error adding attendee:', err);
                        });
                    },

                    removeAttendee(bookingId) {
                        if (!confirm('Are you sure you want to remove this client from the session?')) return;
                        this.$wire.removeAttendee(bookingId).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error removing attendee:', err);
                        });
                    },

                    deleteSingleSession(sessionId) {
                        if (!confirm('¿Deseas eliminar SÓLO esta sesión (este día específico)? Las demás sesiones recurrentes se mantendrán.')) return;
                        this.closeModal();
                        this.$wire.deleteSingleSession(sessionId).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error deleting session:', err);
                        });
                    },

                    deleteSeries(sessionId) {
                        if (!confirm('¿Deseas eliminar TODA la serie recurrente? Se borrarán todas las fechas asociadas a esta sesión.')) return;
                        this.closeModal();
                        this.$wire.deleteSeries(sessionId).then(() => {
                            window.location.reload();
                        }).catch(err => {
                            console.error('Error deleting series:', err);
                        });
                    },

                    deleteSession(sessionId) {
                        this.deleteSingleSession(sessionId);
                    },
                }));
            });
        </script>
        <style>
            /* Lightbox Backdrop Blur Overlay */
            .modal-backdrop-blur {
                position: fixed !important;
                inset: 0 !important;
                background-color: rgba(15, 23, 42, 0.65) !important;
                -webkit-backdrop-filter: blur(12px) saturate(180%) !important;
                backdrop-filter: blur(12px) saturate(180%) !important;
                transition: opacity 0.2s ease-in-out !important;
            }

            /* Lightbox Modal Sizing & Scrolling (Clamped to 90vh) */
            .modal-lightbox-dialog {
                max-height: 90vh !important;
                display: flex !important;
                flex-direction: column !important;
                overflow: hidden !important;
                box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
            }
            .modal-form-wrapper {
                display: flex !important;
                flex-direction: column !important;
                flex: 1 1 auto !important;
                min-height: 0 !important;
                max-height: 100% !important;
                overflow: hidden !important;
            }
            .modal-body-scroll {
                padding: 1.75rem 1.75rem !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                flex: 1 1 auto !important;
                min-height: 0 !important;
                -webkit-overflow-scrolling: touch !important;
                overscroll-behavior: contain !important;
            }
            @media (min-width: 640px) {
                .modal-body-scroll {
                    padding: 2rem 2.25rem !important;
                }
            }
            .modal-body-scroll::-webkit-scrollbar {
                width: 6px !important;
            }
            .modal-body-scroll::-webkit-scrollbar-track {
                background: transparent !important;
            }
            .modal-body-scroll::-webkit-scrollbar-thumb {
                background: rgba(156, 163, 175, 0.4) !important;
                border-radius: 9999px !important;
            }
            .modal-body-scroll::-webkit-scrollbar-thumb:hover {
                background: rgba(156, 163, 175, 0.7) !important;
            }
            .modal-header-pinned {
                padding: 1.5rem 1.75rem !important;
                flex-shrink: 0 !important;
            }
            @media (min-width: 640px) {
                .modal-header-pinned {
                    padding: 1.75rem 2.25rem !important;
                }
            }
            .modal-footer-pinned {
                padding: 1.25rem 1.75rem !important;
                flex-shrink: 0 !important;
            }
            @media (min-width: 640px) {
                .modal-footer-pinned {
                    padding: 1.5rem 2.25rem !important;
                }
            }

            /* Lightbox Responsive Grid & Input Styling */
            .modal-grid-2 {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 1.25rem !important;
            }
            @media (min-width: 640px) {
                .modal-grid-2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
            }

            .modal-input {
                width: 100% !important;
                font-size: 0.875rem !important;
                line-height: 1.25rem !important;
                border-radius: 0.75rem !important;
                padding: 0.65rem 0.95rem !important;
                background-color: #ffffff !important;
                border: 1px solid #d1d5db !important;
                color: #111827 !important;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.04) !important;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
            }
            .dark .modal-input {
                background-color: #111827 !important;
                border-color: rgba(255, 255, 255, 0.12) !important;
                color: #f9fafb !important;
            }
            .modal-input:focus {
                outline: none !important;
                border-color: #3CA7A1 !important;
                box-shadow: 0 0 0 3px rgba(60, 167, 161, 0.22) !important;
            }

            /* Lightbox Section Cards without harsh borders */
            .modal-section-card {
                background-color: #f8fafc !important;
                border-radius: 1.25rem !important;
                padding: 1.25rem 1.5rem !important;
                border: none !important;
            }
            .dark .modal-section-card {
                background-color: rgba(31, 41, 55, 0.45) !important;
                border: none !important;
            }
            .modal-section-card-emerald {
                background-color: rgba(236, 253, 245, 0.7) !important;
                border: none !important;
            }
            .dark .modal-section-card-emerald {
                background-color: rgba(6, 78, 59, 0.25) !important;
                border: none !important;
            }

            /* Brand Primary Teal Action Buttons */
            .btn-calendar-primary {
                background-color: #3CA7A1 !important;
                color: #ffffff !important;
                border: 1px solid #3CA7A1 !important;
            }
            .btn-calendar-primary:hover {
                background-color: #2e827d !important;
                border-color: #2e827d !important;
                color: #ffffff !important;
            }
            .btn-calendar-primary:active {
                background-color: #266b67 !important;
                border-color: #266b67 !important;
                color: #ffffff !important;
            }

            /* 1. FullCalendar Toolbar & Container Styles matching fullcalendar.io/demos */
            .fc-daygrid-day-frame, .fc-timegrid-slot {
                cursor: pointer !important;
            }

            .fc .fc-toolbar.fc-header-toolbar {
                margin-bottom: 1.5rem !important;
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 0.75rem !important;
                flex-wrap: wrap !important;
            }

            .fc .fc-toolbar-title {
                font-size: 1.35rem !important;
                font-weight: 800 !important;
                letter-spacing: -0.025em !important;
                color: #0f172a !important;
            }
            .dark .fc .fc-toolbar-title {
                color: #f8fafc !important;
            }

            /* Segmented Capsule Button Group (Prev/Next and View Switcher) */
            .fc .fc-button-group {
                display: inline-flex !important;
                align-items: center !important;
                background-color: rgba(243, 244, 246, 0.95) !important;
                border: 1px solid rgba(229, 231, 235, 0.9) !important;
                border-radius: 0.875rem !important;
                padding: 3px !important;
                gap: 2px !important;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            }
            .dark .fc .fc-button-group {
                background-color: rgba(31, 41, 55, 0.9) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
            }

            .fc .fc-button-group > .fc-button {
                background-color: transparent !important;
                border: 1px solid transparent !important;
                border-radius: 0.625rem !important;
                color: #4b5563 !important;
                font-size: 0.8125rem !important;
                font-weight: 600 !important;
                line-height: 1.25rem !important;
                padding: 0.35rem 0.75rem !important;
                margin: 0 !important;
                box-shadow: none !important;
                text-transform: capitalize !important;
                transition: all 0.15s ease !important;
                cursor: pointer !important;
            }
            .dark .fc .fc-button-group > .fc-button {
                color: #9ca3af !important;
            }

            .fc .fc-button-group > .fc-button:hover:not(:disabled) {
                background-color: rgba(255, 255, 255, 0.75) !important;
                color: #0f172a !important;
            }
            .dark .fc .fc-button-group > .fc-button:hover:not(:disabled) {
                background-color: rgba(255, 255, 255, 0.08) !important;
                color: #ffffff !important;
            }

            .fc .fc-button-group > .fc-button.fc-button-active,
            .fc .fc-button-group > .fc-button-primary:not(:disabled).fc-button-active {
                background-color: #ffffff !important;
                color: #0f172a !important;
                font-weight: 700 !important;
                border-color: rgba(0, 0, 0, 0.06) !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08) !important;
            }
            .dark .fc .fc-button-group > .fc-button.fc-button-active,
            .dark .fc .fc-button-group > .fc-button-primary:not(:disabled).fc-button-active {
                background-color: #111827 !important;
                color: #ffffff !important;
                border-color: rgba(255, 255, 255, 0.12) !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3) !important;
            }

            /* Modern Today Glass Pill Button */
            .fc .fc-today-button {
                background-color: rgba(243, 244, 246, 0.95) !important;
                border: 1px solid rgba(229, 231, 235, 0.9) !important;
                color: #374151 !important;
                border-radius: 0.75rem !important;
                font-size: 0.8125rem !important;
                font-weight: 600 !important;
                padding: 0.4rem 0.875rem !important;
                text-transform: capitalize !important;
                margin-left: 0.5rem !important;
                box-shadow: none !important;
                transition: all 0.15s ease !important;
                cursor: pointer !important;
            }
            .dark .fc .fc-today-button {
                background-color: rgba(31, 41, 55, 0.9) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                color: #e5e7eb !important;
            }
            .fc .fc-today-button:hover:not(:disabled) {
                background-color: #ffffff !important;
                border-color: #cbd5e1 !important;
                color: #0f172a !important;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            }
            .dark .fc .fc-today-button:hover:not(:disabled) {
                background-color: rgba(255, 255, 255, 0.08) !important;
                border-color: rgba(255, 255, 255, 0.2) !important;
                color: #ffffff !important;
            }
            .fc .fc-today-button:disabled {
                opacity: 0.4 !important;
                cursor: not-allowed !important;
            }

            /* Crisp Chevron Navigation Icons */
            .fc .fc-button .fc-icon {
                font-size: 1.15em !important;
                vertical-align: middle !important;
            }

            /* Day Column Headers (Uppercase & Tracked) */
            .fc-col-header-cell {
                background-color: rgba(249, 250, 251, 0.6) !important;
                padding: 0.6rem 0 !important;
                border-color: rgba(226, 232, 240, 0.8) !important;
            }
            .dark .fc-col-header-cell {
                background-color: rgba(17, 24, 39, 0.5) !important;
                border-color: rgba(255, 255, 255, 0.08) !important;
            }
            .fc-col-header-cell-cushion {
                font-size: 0.75rem !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.06em !important;
                color: #64748b !important;
                text-decoration: none !important;
                padding: 6px !important;
            }
            .dark .fc-col-header-cell-cushion {
                color: #94a3b8 !important;
            }

            /* Month Grid Day Numbers */
            .fc-daygrid-day-number {
                font-size: 0.8125rem !important;
                font-weight: 500 !important;
                color: #475569 !important;
                text-decoration: none !important;
                padding: 6px 8px !important;
            }
            .dark .fc-daygrid-day-number {
                color: #94a3b8 !important;
            }
            .fc-day-other .fc-daygrid-day-number {
                opacity: 0.35 !important;
            }

            /* Circular Badge for Today (FullCalendar Brand Color #3CA7A1) */
            .fc-day-today {
                background-color: rgba(60, 167, 161, 0.04) !important;
            }
            .dark .fc-day-today {
                background-color: rgba(60, 167, 161, 0.08) !important;
            }
            .fc-day-today .fc-daygrid-day-number {
                background-color: #3CA7A1 !important;
                color: #ffffff !important;
                border-radius: 9999px !important;
                width: 26px !important;
                height: 26px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-weight: 700 !important;
                font-size: 0.75rem !important;
                margin: 4px 6px !important;
                box-shadow: 0 2px 4px rgba(60, 167, 161, 0.3) !important;
            }

            /* Modern Event Cards */
            .fc-daygrid-event {
                border-radius: 0.5rem !important;
                border: none !important;
                border-left: 3px solid currentColor !important;
                padding: 2px 4px !important;
                margin: 2px 3px !important;
                cursor: pointer !important;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
                transition: transform 0.15s ease, box-shadow 0.15s ease !important;
            }
            .fc-daygrid-event:hover {
                transform: translateY(-1px) scale(1.01) !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.12), 0 2px 4px -2px rgba(0, 0, 0, 0.08) !important;
                z-index: 10 !important;
            }
            .fc-timegrid-event {
                border-radius: 0.5rem !important;
                border: none !important;
                border-left: 3px solid currentColor !important;
                padding: 3px 5px !important;
                cursor: pointer !important;
                box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
                transition: transform 0.15s ease, box-shadow 0.15s ease !important;
            }
            .fc-timegrid-event:hover {
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.12) !important;
            }

            /* Grid Lines */
            .fc-theme-standard td, .fc-theme-standard th {
                border-color: rgba(226, 232, 240, 0.75) !important;
            }
            .dark .fc-theme-standard td, .dark .fc-theme-standard th {
                border-color: rgba(255, 255, 255, 0.07) !important;
            }

            /* Selection & Slot Styling */
            .fc-highlight {
                background: rgba(60, 167, 161, 0.15) !important;
                border-radius: 0.375rem !important;
            }
            .fc-timegrid-slot-label-cushion {
                font-size: 0.75rem !important;
                font-weight: 500 !important;
                color: #64748b !important;
                text-transform: uppercase !important;
            }
            .dark .fc-timegrid-slot-label-cushion {
                color: #94a3b8 !important;
            }

            /* Now Indicator */
            .fc-timegrid-now-indicator-line {
                border-color: #ef4444 !important;
                border-width: 2px !important;
            }
            .fc-timegrid-now-indicator-arrow {
                border-color: #ef4444 !important;
            }

            /* Disabled Past Dates */
            .fc-day-past {
                background-color: rgba(248, 250, 252, 0.7) !important;
                cursor: not-allowed !important;
            }
            .dark .fc-day-past {
                background-color: rgba(15, 23, 42, 0.45) !important;
            }
            .fc-day-past .fc-daygrid-day-number {
                opacity: 0.3 !important;
            }
            .fc-timegrid-col.fc-day-past .fc-timegrid-col-frame {
                background-color: rgba(248, 250, 252, 0.7) !important;
                cursor: not-allowed !important;
            }
            .dark .fc-timegrid-col.fc-day-past .fc-timegrid-col-frame {
                background-color: rgba(15, 23, 42, 0.45) !important;
            }

            /* List View */
            .fc-list-table {
                border-color: rgba(226, 232, 240, 0.75) !important;
            }
            .dark .fc-list-table {
                border-color: rgba(255, 255, 255, 0.08) !important;
            }
            .fc-list-day-cushion {
                background-color: rgba(243, 244, 246, 0.7) !important;
                padding: 8px 14px !important;
            }
            .dark .fc-list-day-cushion {
                background-color: rgba(31, 41, 55, 0.7) !important;
            }
            .fc-list-day-text, .fc-list-day-side-text {
                font-weight: 700 !important;
                font-size: 0.8125rem !important;
                color: #1e293b !important;
                text-decoration: none !important;
            }
            .dark .fc-list-day-text, .dark .fc-list-day-side-text {
                color: #f1f5f9 !important;
            }
            .fc-list-event:hover td {
                background-color: rgba(60, 167, 161, 0.08) !important;
                cursor: pointer !important;
            }
        </style>
    @endpush
</x-filament-panels::page>
