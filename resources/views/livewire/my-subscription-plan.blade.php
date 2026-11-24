<x-filament-breezy::grid-section md=2 title="My Membership Plan" description="View your current subscription plan, included benefits, or upgrade/downgrade your membership.">
    <x-filament::card>
        <div class="space-y-6">
            {{-- Current Plan Box --}}
            <div class="rounded-xl border-2 border-primary-500 bg-primary-50/40 dark:bg-primary-950/20 p-4" style="padding: 1.25rem;">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <x-filament::badge color="primary" icon="heroicon-m-check-badge">
                                Current Plan
                            </x-filament::badge>
                            <x-filament::badge :color="$user->is_active ? 'success' : 'warning'">
                                {{ $user->is_active ? 'Active' : 'Pending Payment' }}
                            </x-filament::badge>
                        </div>

                        <h3 class="mt-2 text-xl font-bold text-gray-950 dark:text-white">
                            {{ $currentPlan ? $currentPlan->name_en : 'No Active Membership Plan' }}
                        </h3>

                        @if($currentPlan && $currentPlan->description_en)
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                {{ $currentPlan->description_en }}
                            </p>
                        @endif
                    </div>

                    @if($currentPlan)
                        <div class="text-right">
                            <span class="text-2xl font-extrabold text-gray-950 dark:text-white">
                                ${{ number_format($currentPlan->price, 2) }}
                            </span>
                            <span class="text-xs font-medium uppercase text-gray-500 dark:text-gray-400 block">
                                USD / {{ match($currentPlan->billing_cycle) { 'every_4_weeks' => '4 weeks', default => $currentPlan->billing_cycle } }}
                            </span>
                        </div>
                    @endif
                </div>

                @if($currentPlan && !empty($currentPlan->features))
                    <div class="mt-4 pt-4 border-t border-gray-200/70 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                            Included in your plan
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($currentPlan->features as $feat)
                                <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 text-primary-600 shrink-0" />
                                    <span>{{ $feat['feature_en'] ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Upgrade / Downgrade Options --}}
            @php
                $otherPlans = $availablePlans->filter(fn($p) => !$currentPlan || $p->id !== $currentPlan->id);
                $currentPrice = (float) ($currentPlan?->price ?? 0);
            @endphp

            @if($otherPlans->isNotEmpty())
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-gray-950 dark:text-white">
                        Available Upgrade & Downgrade Options
                    </h4>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                        @foreach($otherPlans as $plan)
                            @php
                                $targetPrice = (float) $plan->price;
                                $isUpgrade = $targetPrice > $currentPrice;
                                $diff = abs($targetPrice - $currentPrice);
                            @endphp

                            <div class="rounded-xl border border-gray-200 dark:border-white/10 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:border-primary-300 transition">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-base text-gray-950 dark:text-white">
                                            {{ $plan->name_en }}
                                        </span>

                                        @if($isUpgrade)
                                            <x-filament::badge color="success" size="sm" icon="heroicon-m-arrow-trending-up">
                                                Upgrade · Pay difference: +${{ number_format($diff, 2) }} USD
                                            </x-filament::badge>
                                        @else
                                            <x-filament::badge color="gray" size="sm" icon="heroicon-m-arrow-trending-down">
                                                Downgrade · ${{ number_format($targetPrice, 2) }} USD / {{ match($plan->billing_cycle) { 'every_4_weeks' => '4 weeks', default => $plan->billing_cycle } }}
                                            </x-filament::badge>
                                        @endif
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Plan price: ${{ number_format($targetPrice, 2) }} USD / {{ match($plan->billing_cycle) { 'every_4_weeks' => '4 weeks', default => $plan->billing_cycle } }}
                                        @if($plan->description_en)
                                            — {{ \Illuminate\Support\Str::limit($plan->description_en, 90) }}
                                        @endif
                                    </p>
                                </div>

                                <div class="shrink-0">
                                    {{ ($this->changePlanAction)(['plan_id' => $plan->id]) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::card>

    <x-filament-actions::modals />
</x-filament-breezy::grid-section>
