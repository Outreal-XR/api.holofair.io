<?php

namespace App\Http\Controllers;

use App\Models\Metaverse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function getGeneralSettings($metaverse_id)
    {
        $metaverse = Metaverse::find($metaverse_id);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found'
            ], 404);
        }

        $generalSettings = $metaverse->generalSettings;
        return response()->json([
            'message' => 'General Settings retrieved successfully',
            'data' => $generalSettings
        ], 200);
    }

    public function getAvatarSettings($metaverse_id)
    {
        $metaverse = Metaverse::find($metaverse_id);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found'
            ], 404);
        }

        $avatarSettings = $metaverse->avatarSettings;
        return response()->json([
            'message' => 'Avatar Settings retrieved successfully',
            'data' => $avatarSettings
        ], 200);
    }

    public function updateGeneralSettings(Request $request, $metaverse_id)
    {
        //if the request is empty
        if (empty($request->all())) {
            return response()->json([
                'message' => 'Nothing to update'
            ], 200);
        }

        $validation = Validator::make($request->all(), [
            "custom_landing_page" => "nullable|boolean",
            "allow_public_chat" => "nullable|boolean",
            "allow_direct_messages" => "nullable|boolean",
            "allow_direct_calls" => "nullable|boolean",
            "custom_spawn_point" => "nullable|boolean",
            "spawn_point" => "nullable|string"
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $metaverse = Metaverse::find($metaverse_id);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found'
            ], 404);
        }

        $generalSettings = $metaverse->generalSettings;

        if (!$generalSettings) {
            return response()->json([
                'message' => 'General Settings not found'
            ], 404);
        }

        if ($request->custom_spawn_point && $request->custom_spawn_point == true && empty($request->spawn_point)) {
            return response()->json([
                'message' => 'Spawn point is required'
            ], 400);
        }

        if ($request->custom_spawn_point !=  null && boolval($request->custom_spawn_point)  == false) {
            $request["spawn_point"] = null;
        }

        if ($request->spawn_point) {
            $request["custom_spawn_point"] = true;
        }

        $generalSettings->update($request->only([
            "custom_landing_page",
            "allow_public_chat",
            "allow_direct_messages",
            "allow_direct_calls",
            "custom_spawn_point",
            "spawn_point"
        ]));

        return response()->json([
            'message' => 'General Settings updated successfully',
            'data' => $generalSettings
        ], 200);
    }

    public function updateAvatarSettings(Request $request, $metaverse_id)
    {
        //if the request is empty
        if (empty($request->all())) {
            return response()->json([
                'message' => 'Nothing to update'
            ], 200);
        }

        $validation = Validator::make($request->all(), [
            'ready_player_me' => 'nullable|boolean',
            'holofair_avatar' => 'nullable|boolean',
            'holofair_avatar_id' => 'nullable|numeric',
            'custom_avatar' => 'nullable|boolean',
            'custom_avatar_url' => 'nullable|string'
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $metaverse = Metaverse::find($metaverse_id);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found'
            ], 404);
        }

        $avatarSettings = $metaverse->avatarSettings;

        if (!$avatarSettings) {
            return response()->json([
                'message' => 'Avatar Settings not found'
            ], 404);
        }

        if ($request->holofair_avatar && empty($request->holofair_avatar_id)) {
            return response()->json([
                'message' => 'Holofair Avatar ID is required'
            ], 400);
        }

        if ($request->custom_avatar && empty($request->custom_avatar_url)) {
            return response()->json([
                'message' => 'Custom Avatar URL is required'
            ], 400);
        }

        if ($request->holofair_avatar != null && boolval($request->holofair_avatar) == false) {
            $request["holofair_avatar_id"] = null;
        }

        if ($request->holofair_avatar_id) {
            $request["holofair_avatar"] = true;
        }

        if ($request->custom_avatar != null && boolval($request->custom_avatar) == false) {
            $request["custom_avatar_url"] = null;
        }

        if ($request->custom_avatar_url) {
            $request["custom_avatar"] = true;
        }

        $avatarSettings->update($request->only([
            'ready_player_me',
            'holofair_avatar',
            'holofair_avatar_id',
            'custom_avatar',
            'custom_avatar_url',
        ]));

        return response()->json([
            'message' => 'Avatar Settings updated successfully',
            'data' => $avatarSettings
        ], 200);
    }
}
