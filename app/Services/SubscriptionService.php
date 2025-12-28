<?php

namespace App\Services;

use App\Models\User;

class SubscriptionService
{
    public function activatePlan(User $user, string $plan, int $days = 30): void
    {
        $user->subscription_plan = $plan;
        $user->plan_started_at = now();

        if ($plan === 'free') {
            $user->is_subscribed = false;
            $user->subscribed_until = null;
        } else {
            $user->is_subscribed = true;
            $user->subscribed_until = now()->addDays($days);
        }

        $user->save();
    }
}
