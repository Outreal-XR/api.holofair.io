<?php

namespace App\Http\Controllers;

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
            "images.*.file" => "required|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first()
            ], 400);
        }

        //upload images to s3
        $images = [];
        foreach ($request->images as $image) {
            $path = "props/images";
            $imageName = $image["key"] . "." . $image["file"]->extension();
            $uploadPath =  Storage::disk("s3")->putFileAs($path, $image["file"], $imageName);
        }

        return response()->json([
            "message" => "Images uploaded successfully",
        ], 200);
    }
}
