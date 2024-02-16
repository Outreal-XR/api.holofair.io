<?php

namespace App\Observers;

use App\Models\Metaverse;
use App\Models\MetaverseSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class MetaverseObserver
{
    /**
     * Handle the Metaverse "created" event.
     */
    public function created(Metaverse $metaverse): void
    {
        //add metaverse settings
        try {
            DB::beginTransaction();

            MetaverseSetting::chunkById(1000, function ($settings) use ($metaverse) {
                foreach ($settings as $setting) {
                    $metaverse->settings()->attach($setting->id, ['value' => $setting->default_value]);
                }
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
