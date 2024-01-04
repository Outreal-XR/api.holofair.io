<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //add free plan to user
        $freePlan = Plan::where('name', 'Free')->first();

        $user->subscription()->create([
            'plan_id' => $freePlan->id,
            'end_at' => now()->addMonth(),
        ]);
    }
}
