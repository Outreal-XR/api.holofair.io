<?php

namespace App\Http\Controllers;

use App\Models\MetaverseSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class MetaverseSettingController extends Controller
{
    public function index()
    {
        $settings = MetaverseSetting::all();

        return response()->json([
            'settings' => $settings
        ]);
    }

    public function addSettings()
    {
        $settings = [
            [
                "display_name" => "Language by default",
                "name" => "default_language",
                "default_value" => "en",

            ],
            [
                "display_name" => "Graphic settings by default",
                "name" => "default_graphic_settings",
            ],
            [
                "display_name" => "Enable ReadyPlayerMe",
                "name" => "enable_ready_player_me",
                "default_value" => "false",

            ],
            [
                "display_name" => "Allow users' avatars",
                "name" => "allow_users_avatars",
                "default_value" => "false",

            ],
            [
                "display_name" => "Enable Holofair avatar options",
                "name" => "enable_holofair_avatar_options",
                "default_value" => "false",

            ],
        ];


        DB::beginTransaction();

        try {

            foreach ($settings as $setting) {
                MetaverseSetting::create($setting);
            }

            DB::commit();

            return response()->json([
                'message' => 'Settings added successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error adding metaverse settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'default_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            $metaverseSetting = MetaverseSetting::create($request->only([
                'name',
                'display_name',
                'description',
                'default_value',
            ]));

            DB::commit();
            return response()->json([
                'message' => 'Setting created successfully',
                'setting' => $metaverseSetting
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
