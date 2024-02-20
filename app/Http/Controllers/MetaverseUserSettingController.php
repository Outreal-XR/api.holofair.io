<?php

namespace App\Http\Controllers;

use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MetaverseUserSettingController extends Controller
{
    public function index($metaverseId)
    {
        $user = Auth::user();

        //find the metaverse
        $metaverse = $user->metaverses->find($metaverseId);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found'
            ], 404);
        }

        //get the settings
        $settings = $metaverse->settings;

        //get the user settings
        $userSettings = $user->settingsPerMetaverses->where('metaverse_id', $metaverse->id);

        //index the user settings by the metaverse setting id
        $userSettings = $userSettings->keyBy('metaverse_setting_id');

        //map the settings and add the user value
        $settings = $settings->map(function ($setting) use ($userSettings) {
            $setting->userValue = $userSettings->get($setting->id)->value ?? $setting->pivot->value;
            return $setting;
        });

        return response()->json([
            'settings' => $settings
        ]);
    }

    //create or update the user settings
    public function createOrUpdate(Request $request, $metaverseId)
    {
        $user = Auth::user();

        //find the metaverse
        $metaverse = $user->metaverses->find($metaverseId);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found'
            ], 404);
        }

        //validate the request
        $validator = Validator::make($request->all(), [
            'setting_id' => 'required|exists:metaverse_settings,id',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first()
            ], 400);
        }

        //find the setting in the metaverse
        $setting = $metaverse->settings->find($request->setting_id);

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }

        //get the user settings
        $userSettings = $user->settingsPerMetaverses->where('metaverse_id', $metaverse->id);

        //index the user settings by the metaverse setting id
        $userSettings = $userSettings->keyBy('metaverse_setting_id');

        //check if the user has the setting, if not create it
        if (!$userSettings->has($setting->id)) {
            $userSetting = $user->settingsPerMetaverses()->create([
                'metaverse_id' => $metaverse->id,
                'metaverse_setting_id' => $setting->id,
                'value' => $request->value
            ]);
        } else {
            $userSetting = $userSettings->get($setting->id);
            $userSetting->value = $request->value;
            $userSetting->save();
        }

        return response()->json([
            'message' => 'Setting updated successfully'
        ]);
    }
}
