<?php

namespace App\Observers;

use App\Models\Metaverse;

class MetaverseObserver
{
    /**
     * Handle the Metaverse "created" event.
     */
    public function created(Metaverse $metaverse): void
    {
        //create general settings
        $metaverse->generalSettings()->create();

        //create avatar settings
        $metaverse->avatarSettings()->create();
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
