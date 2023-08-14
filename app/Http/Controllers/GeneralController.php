<?php

namespace App\Http\Controllers;

use App\Traits\MediaTrait;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Echo_;
use Termwind\Components\Dd;

class GeneralController extends Controller
{
    use MediaTrait;
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

        $localDecodedFilePath = "props/images/";
        $s3Destination = 'props/images/';
        $extension = '.png';

        if (!file_exists(public_path($localDecodedFilePath))) {
            mkdir(public_path($localDecodedFilePath), 0777, true);
        }

        //upload images to s3
        $props = [];
        foreach ($request->images as $image) {

            $file = $image["file"];
            $key = $image["key"];

            $filename = $key . $extension;
            $decodedContent = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));

            $localFile = public_path($localDecodedFilePath . $filename);

            //save the file locally
            $save = file_put_contents($localFile, $decodedContent);

            if (!$save) {
                return response()->json([
                    'error' => 'failed to save the image locally'
                ], 400);
            }

            try {
                $path = Storage::disk('s3')->put($s3Destination . $filename, file_get_contents($localFile));

                if ($path) {
                    $url =  Storage::disk("s3")->url($s3Destination . $filename);
                    $destination =
                        str_replace("holofair-mena.s3.me-south-1.amazonaws.com", "cdn.holofair.net", $url);

                    $props[] = [
                        "file" => $filename,
                        "url" => $destination
                    ];
                } else {
                    return response()->json([
                        'error' => 'failed to save the image in s3'
                    ], 400);
                }
            } catch (S3Exception $e) {
                return response()->json([
                    'awsError' => $e->getMessage()
                ], 400);
            }
        }

        return response()->json([
            "message" => "Images uploaded successfully",
            "props" => $props
        ], 200);
    }

    public function testBinary(Request $request)
    {

        $encodedFile = $request->file;
        $prop_key = $request->key;
        $decodedContent = base64_decode($encodedFile);

        $localDecodedFilePath = "props/images/";
        $s3Destination = 'props/images/';
        $extension = '.png';
        $filename =  $prop_key . $extension;

        if (!file_exists(public_path($localDecodedFilePath))) {
            mkdir(public_path($localDecodedFilePath), 0777, true);
        }

        $localFile = public_path($localDecodedFilePath . $filename);

        //save the file locally
        $save = file_put_contents($localFile, $decodedContent);

        if (!$save) {
            return response()->json([
                'error' => 'failed to save the image locally'
            ], 400);
        }

        try {
            $path = Storage::disk('s3')->put($s3Destination . $filename, file_get_contents($localFile));

            if ($path) {
                $url =  Storage::disk("s3")->url($s3Destination . $filename);
                $destination =
                    str_replace("holofair-mena.s3.me-south-1.amazonaws.com", "cdn.holofair.net", $url);

                return response()->json([
                    'url' => $destination
                ], 200);
            } else {
                return response()->json([
                    'error' => 'failed to save the image 0in s3'
                ], 400);
            }
        } catch (S3Exception $e) {
            return response()->json([
                'awsError' => $e->getMessage()
            ], 400);
        }
    }
}
