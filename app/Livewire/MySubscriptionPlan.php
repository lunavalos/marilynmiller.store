<?php

namespace App\Livewire;

use App\Models\Plan;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class MySubscriptionPlan extends MyProfileComponent
{
    protected string $view = 'livewire.my-subscription-plan';

    public static $sort = 15;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasRole('subscribed')
            && ! $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function changePlanAction(): Action
    {
        return Action::make('changePlan')
            ->label(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) ($plan?->price ?? 0);
                $isUpgrade = $targetPrice > $currentPrice;
                $diff = abs($targetPrice - $currentPrice);

                return $isUpgrade
                    ? 'Upgrade (+$'.number_format($diff, 2).' USD)'
                    : 'Downgrade';
            })
            ->icon(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) ($plan?->price ?? 0);

                return $targetPrice > $currentPrice
                    ? 'heroicon-m-arrow-up-circle'
                    : 'heroicon-m-arrow-down-circle';
            })
            ->color(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) ($plan?->price ?? 0);

                return $targetPrice > $currentPrice ? 'primary' : 'gray';
            })
            ->size('sm')
            ->requiresConfirmation()
            ->modalIcon(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) ($plan?->price ?? 0);

                return $targetPrice > $currentPrice
                    ? 'heroicon-o-arrow-trending-up'
                    : 'heroicon-o-arrow-trending-down';
            })
            ->modalHeading(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) ($plan?->price ?? 0);

                return ($targetPrice > $currentPrice ? 'Upgrade to ' : 'Downgrade to ').($plan?->name_en ?? 'Plan');
            })
            ->modalDescription(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                if (! $plan) {
                    return '';
                }
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) $plan->price;
                $isUpgrade = $targetPrice > $currentPrice;
                $diff = abs($targetPrice - $currentPrice);

                $cycleLabel = match ($plan->billing_cycle) {
                    'every_4_weeks' => '4 weeks',
                    default => $plan->billing_cycle,
                };

                if ($isUpgrade) {
                    return "Are you sure you want to upgrade your membership to {$plan->name_en} ($".number_format($targetPrice, 2)." USD / {$cycleLabel})? You will be charged the difference of $".number_format($diff, 2).' USD.';
                }

                return "Are you sure you want to downgrade your membership to {$plan->name_en} ($".number_format($targetPrice, 2)." USD / {$cycleLabel})?";
            })
            ->modalSubmitActionLabel(function (array $arguments): string {
                $plan = Plan::find($arguments['plan_id'] ?? null);
                $user = Filament::getCurrentPanel()->auth()->user();
                $currentPrice = (float) ($user->plan?->price ?? 0);
                $targetPrice = (float) ($plan?->price ?? 0);
                $diff = abs($targetPrice - $currentPrice);

                return $targetPrice > $currentPrice
                    ? 'Confirm & Pay $'.number_format($diff, 2).' USD'
                    : 'Confirm Downgrade';
            })
            ->action(function (array $arguments) {
                $this->changePlan((int) ($arguments['plan_id'] ?? 0));
            });
    }

    public function changePlan(int $planId)
    {
        $user = Filament::getCurrentPanel()->auth()->user();
        $currentPlan = $user->plan;
        $targetPlan = Plan::where('is_active', true)->findOrFail($planId);

        if ($currentPlan && $currentPlan->id === $targetPlan->id) {
            return;
        }

        $currentPrice = (float) ($currentPlan?->price ?? 0);
        $targetPrice = (float) $targetPlan->price;
        $isUpgrade = $targetPrice > $currentPrice;
        $difference = max(0, $targetPrice - $currentPrice);

        if ($isUpgrade && ! empty($targetPlan->stripe_payment_link)) {
            $paymentLink = $targetPlan->stripe_payment_link;
            $separator = parse_url($paymentLink, PHP_URL_QUERY) ? '&' : '?';
            $checkoutUrl = $paymentLink.$separator.'client_reference_id='.$user->id.'&prefilled_email='.urlencode($user->email);

            return redirect()->away($checkoutUrl);
        }

        $user->update([
            'plan_id' => $targetPlan->id,
            'is_active' => true,
        ]);

        if ($isUpgrade) {
            Notification::make()
                ->success()
                ->title("Upgraded to {$targetPlan->name_en}!")
                ->body('Charged difference of $'.number_format($difference, 2).' USD (Payment simulation active).')
                ->send();
        } else {
            $cycleLabel = match ($targetPlan->billing_cycle) {
                'every_4_weeks' => '4 weeks',
                default => $targetPlan->billing_cycle,
            };
            Notification::make()
                ->info()
                ->title("Switched to {$targetPlan->name_en}")
                ->body("Your membership has been updated to {$targetPlan->name_en} (\${$targetPlan->price} USD / {$cycleLabel}).")
                ->send();
        }
    }

    public function render()
    {
        $user = Filament::getCurrentPanel()->auth()->user()->load('plan');
        $currentPlan = $user->plan;
        $availablePlans = Plan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        return view($this->view, [
            'user' => $user,
            'currentPlan' => $currentPlan,
            'availablePlans' => $availablePlans,
        ]);
    }
}
