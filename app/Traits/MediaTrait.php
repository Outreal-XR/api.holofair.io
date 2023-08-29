<?php

namespace App\Traits;

trait MediaTrait
{
    public function uploadMedia($file, $path)
    {
        $filename = time() . "-" . $file->getClientOriginalName();

        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0777, true);
        }

        $file->move(public_path($path), $filename);
        return $path . '/' . $filename;
    }
}
