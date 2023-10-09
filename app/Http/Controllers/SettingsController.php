<?php

namespace App\Http\Controllers;

use App\Models\Metaverse;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function addSettings()
    {
        $settings = [
            [
                "display_name" => "Allow public chat in metaverse",
                "name" => "allow_public_chat",
                "category" => "general",
                "default_value" => "false",

            ],
            [
                "display_name" => "Allow direct messages between users",
                "name" => "allow_direct_messages",
                "category" => "general",
                "default_value" => "false",

            ],
            [
                "display_name" => "Allow direct calls between users",
                "name" => "allow_direct_calls",
                "category" => "general",
                "default_value" => "false",

            ],
            [
                "display_name" => "Ready Player Me",
                "name" => "allow_ready_player_me",
                "category" => "avatar",
                "default_value" => "false",

            ],
            [
                "display_name" => "Holofair Avatar",
                "name" => "holofair_avatar",
                "category" => "avatar",
                "default_value" => "false",

            ],
            [
                "display_name" => "Holofair Avatar URL",
                "name" => "holofair_avatar_url",
                "category" => "avatar",
                "default_value" => "https://holofair.com/avatar.glb",

            ],
            [
                "display_name" => "Upload custom avatar",
                "name" => "upload_custom_avatar",
                "category" => "avatar",
                "default_value" => "false",

            ],
            [
                "display_name" => "Custom avatar URL",
                "name" => "custom_avatar_url",
                "category" => "avatar",
                "default_value" => null,
            ],
            [
                "display_name" => "Quality",
                "name" => "quality",
                "category" => "graphics",
                "default_value" => "default",
            ],
            [
                "display_name" => "Target Frame Rate",
                "name" => "target_frame_rate",
                "category" => "graphics",
                "default_value" => "60",
            ],
            [
                "display_name" => "Set custom spawn point",
                "name" => "set_custom_spawn_point",
                "category" => "custom",
                "default_value" => "false",
            ],
            [
                "display_name" => "Custom spawn point",
                "name" => "custom_spawn_point",
                "category" => "custom",
                "default_value" => null,
            ]
        ];


        DB::beginTransaction();

        try {

            foreach ($settings as $setting) {
                Setting::create($setting);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error adding settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testObserver()
    {
        $settings = Setting::all();
        $metaverses = Metaverse::all();

        DB::beginTransaction();

        try {

            foreach ($settings as $setting) {
                foreach ($metaverses as $metaverse) {
                    if (!$metaverse->settings->contains($setting->id)) {
                        $metaverse->settings()->attach($setting->id, ['value' => $setting->default_value]);
                    }
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'Settings added successfully',
                'data' => $metaverses
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error adding settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMetaverseSettings($metaverse_id)
    {
        $metaverse = Metaverse::with('settings')->findOrFail($metaverse_id);


        return response()->json([
            'message' => 'Settings retrieved successfully',
            'data' => $metaverse->settings,
        ], 200);
    }


    public function updateMetaverseSetting(Request $request, $id, $metaverse_id)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $metaverse  = Metaverse::with(['settings', 'invitedUsers'])->findOrFail($metaverse_id);

        if (!$metaverse->canUpdateMetaverse()) {
            return response()->json([
                'message' => 'You are not authorized to update this metaverse',
            ], 401);
        }

        if (!$metaverse->settings->contains($id)) {
            return response()->json([
                'message' => 'Setting not found',
            ], 404);
        }

        $metaverseSetting = $metaverse->settings()->updateExistingPivot($id, ['value' => $request->value]);

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => $metaverseSetting,
        ], 200);
    }
}
