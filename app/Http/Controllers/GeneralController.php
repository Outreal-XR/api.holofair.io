<?php

namespace App\Http\Controllers;

use App\Mail\testEmail;
use App\Traits\MediaTrait;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\Echo_;
use Termwind\Components\Dd;
use Illuminate\Support\Str;

class GeneralController extends Controller
{
    use MediaTrait;
    public function uploadPropImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "key" => "required|string",
            "image" => "required|string",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first()
            ], 400);
        }

        $localDecodedFilePath = "props/images/";
        $s3Destination = 'props/images/';
        $extension = '.png';

        $storageFolder = storage_path("app/public/" . $localDecodedFilePath);

        if (!file_exists($storageFolder)) {
            mkdir($storageFolder, 0777, true);
        }

        //upload images to s3

        $file = $request->image;
        $key = $request->key;

        $filename = $key . $extension;
        $decodedContent = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file));

        $localFile = $storageFolder . $filename;

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
                    "filename" => $filename,
                    'url' => $destination
                ], 200);
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

    public function uploadPropVariablesFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "file" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first()
            ], 400);
        }

        $response = [
            'filename' => '',
            'url' => '',
            'message' => ''
        ];

        //check if file is string
        if (is_string($request->file)) {
            /**
             * @todo find the link of the file and save it in the database           
             */

            $response['message'] = 'String file';
        } else {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();

            $s3Destination = 'props/variables/';

            $s3Client = new S3Client([
                'region' => env('AWS_DEFAULT_REGION'),
                'version' => 'latest',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ]
            ]);
            try {
                $upload = $s3Client->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key' => $s3Destination . $filename,
                    'Body' => file_get_contents($file),

                ]);
                $dest = str_replace("holofair-mena.s3.me-south-1.amazonaws.com", "cdn.holofair.net", $upload['ObjectURL']);

                $response["filename"] = $filename;
                $response['url'] = $dest;
                $response['message'] = $filename . ' Uploaded successfully';
            } catch (S3Exception $e) {
                return response()->json([
                    'awsError' => $e->getMessage()
                ], 400);
            }
        }

        return response()->json($response, 200);
    }
}
