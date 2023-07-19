<?php

namespace App\Traits;

trait MediaTrait
{
    public function uploadMedia($file, $path)
    {
        $filename = time() . "-" . $file->getClientOriginalName();
        $file->move(public_path($path), $filename);
        return $path;
    }
}
