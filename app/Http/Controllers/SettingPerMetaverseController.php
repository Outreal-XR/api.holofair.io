<?php

namespace App\Http\Controllers;

use App\Models\Metaverse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class SettingPerMetaverseController extends Controller
{
    public function index($metaverseId)
    {
        $metaverse = Metaverse::find($metaverseId);
        if (!$metaverse) {
            return response()->json('Metaverse not found', Response::HTTP_NOT_FOUND);
        }

        $settings = $metaverse->settings;

        return response()->json([
            'settings' => $settings
        ]);
    }

    public function create(Request $request, $metaverseId)
    {
        $validator = Validator::make($request->all(), [
            'metaverse_setting_id' => 'required|integer|exists:metaverse_settings,id',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        $metaverse = Metaverse::find($metaverseId);
        if (!$metaverse) {
            return response()->json('Metaverse not found', Response::HTTP_NOT_FOUND);
        }

        if ($metaverse->settings()->where('metaverse_setting_id', $request->metaverse_setting_id)->where('value', $request->value)->exists()) {
            return response()->json('Metaverse setting already exists', Response::HTTP_BAD_REQUEST);
        }

        $metaverse->settings()->attach($request->metaverse_setting_id, ['value' => $request->value]);

        return response()->json('Metaverse setting created', Response::HTTP_CREATED);
    }

    public function update(Request $request, $metaverseId)
    {
        $validator = Validator::make($request->all(), [
            'metaverse_setting_id' => 'required|integer|exists:settings_per_metaverse,id',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        $metaverse = Metaverse::find($metaverseId);
        if (!$metaverse) {
            return response()->json('Metaverse not found', Response::HTTP_NOT_FOUND);
        }

        $metaverseSetting = $metaverse->settings()->where('settings_per_metaverse.id', $request->metaverse_setting_id)->first();
        if (!$metaverseSetting) {
            return response()->json('Metaverse setting not found', Response::HTTP_NOT_FOUND);
        }

        $metaverseSetting->pivot->value = $request->value;
        $metaverseSetting->pivot->save();

        return response()->json('Metaverse setting updated', Response::HTTP_OK);
    }
}
