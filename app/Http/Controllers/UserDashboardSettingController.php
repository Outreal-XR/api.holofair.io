<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class UserDashboardSettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $settings = $user->dashboardSettings;

        return response()->json([
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.id' => 'required|integer|exists:dashboard_settings,id',
            'settings.*.value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();
        $settings = $request->settings;

        foreach ($settings as $setting) {
            $user->dashboardSettings()->updateExistingPivot($setting['id'], ['value' => $setting['value']]);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'userSettings' => $user->dashboardSettings
        ], Response::HTTP_OK);
    }
}
