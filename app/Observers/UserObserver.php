<?php

namespace App\Observers;

use App\Models\DashboardSetting;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //add user dashboard settings
        try {
            DB::beginTransaction();

            DashboardSetting::chunkById(1000, function ($settings) use ($user) {
                foreach ($settings as $setting) {
                    $user->dashboardSettings()->attach($setting->id, ['value' => $setting->default_value]);
                }
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
