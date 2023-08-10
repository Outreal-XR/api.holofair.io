<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class GeneralController extends Controller
{
    public function uploadPropImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "images" => "array|required|min:1|max:100",
            "images.*.key" => "required|string",
            "images.*.file" => "required|string",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first()
            ], 400);
        }

        //upload images to s3
        $props = [];
        foreach ($request->images as $image) {

            $path = "props/images";
            $file = $image["file"];
            $key = $image["key"];
            $extension = 'png';
            $filename = $key . '.' . $extension;
            $file = base64_decode($file);

            $finalPath = $path . '/' . $filename;

            //upload locally
            // Storage::disk('public')->put($finalPath, $file, 'public');

            Storage::disk('s3')->put($finalPath, $file, 'public');


            $props[] = Storage::disk('s3')->url($finalPath);
        }

        return response()->json([
            "message" => "Images uploaded successfully",
            "props" => $props
        ], 200);
    }
}
