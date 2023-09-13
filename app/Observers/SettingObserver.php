<?php

namespace App\Observers;

use App\Models\Metaverse;
use App\Models\Setting;

class SettingObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        $metaverses = Metaverse::whereDoesntHave('settings', function ($query) use ($setting) {
            $query->where('setting_id', $setting->id);
        })->get();

        foreach ($metaverses as $metaverse) {
            $metaverse->settings()->attach($setting->id, ['value' => $setting->default_value]);
        }
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        $metaverses = Metaverse::whereHas('settings', function ($query) use ($setting) {
            $query->where('setting_id', $setting->id);
        })->get();

        foreach ($metaverses as $metaverse) {
            $metaverse->settings()->updateExistingPivot($setting->id, ['value' => $setting->default_value]);
        }
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        //
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $setting): void
    {
        //
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $setting): void
    {
        //
    }
}
