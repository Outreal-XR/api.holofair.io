<?php

namespace App\Observers;

use App\Models\Metaverse;
use App\Models\Setting;

class MetaverseObserver
{
    /**
     * Handle the Metaverse "created" event.
     */
    public function created(Metaverse $metaverse): void
    {
        $settings = Setting::all();

        foreach ($settings as $setting) {
            $metaverse->settings()->attach($setting->id, ['value' => $setting->default_value]);
        }
    }

    /**
     * Handle the Metaverse "updated" event.
     */
    public function updated(Metaverse $metaverse): void
    {
        //
    }

    /**
     * Handle the Metaverse "deleted" event.
     */
    public function deleted(Metaverse $metaverse): void
    {
        //
    }

    /**
     * Handle the Metaverse "restored" event.
     */
    public function restored(Metaverse $metaverse): void
    {
        //
    }

    /**
     * Handle the Metaverse "force deleted" event.
     */
    public function forceDeleted(Metaverse $metaverse): void
    {
        //
    }
}
