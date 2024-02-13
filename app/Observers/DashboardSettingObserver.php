<?php

namespace App\Observers;

use App\Models\DashboardSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardSettingObserver
{
    /**
     * Handle the DashboardSetting "created" event.
     */
    public function created(DashboardSetting $dashboardSetting): void
    {
        //add setting to all users
        try {
            User::chunkById(1000, function ($users) use ($dashboardSetting) {
                foreach ($users as $user) {
                    $user->dashboardSettings()->attach($dashboardSetting->id, ['value' => $dashboardSetting->default_value]);
                }
            });

            DB::commit();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Handle the DashboardSetting "updated" event.
     */
    public function updated(DashboardSetting $dashboardSetting): void
    {
        //
    }

    /**
     * Handle the DashboardSetting "deleted" event.
     */
    public function deleted(DashboardSetting $dashboardSetting): void
    {
        //
    }

    /**
     * Handle the DashboardSetting "restored" event.
     */
    public function restored(DashboardSetting $dashboardSetting): void
    {
        //
    }

    /**
     * Handle the DashboardSetting "force deleted" event.
     */
    public function forceDeleted(DashboardSetting $dashboardSetting): void
    {
        //
    }
}
