<?php

namespace App\Observers;

use App\Models\Metaverse;
use App\Models\MetaverseSetting;
use Illuminate\Support\Facades\DB;

class MetaverseSettingObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(MetaverseSetting $metaverseSetting): void
    {
        //add settings to all metaverses
        try {
            Metaverse::chunkById(1000, function ($metaverses) use ($metaverseSetting) {
                foreach ($metaverses as $metaverse) {
                    $metaverse->settings()->attach($metaverseSetting->id, ['value' => $metaverseSetting->default_value]);
                }
            });

            DB::commit();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(MetaverseSetting $metaverseSetting): void
    {
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(MetaverseSetting $metaverseSetting): void
    {
        //
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(MetaverseSetting $metaverseSetting): void
    {
        //
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(MetaverseSetting $metaverseSetting): void
    {
        //
    }
}
