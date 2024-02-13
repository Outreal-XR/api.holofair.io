<?php

namespace App\Http\Controllers;

use App\Models\DashboardSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class DashboardSettingController extends Controller
{
    public function index()
    {
        $settings = DashboardSetting::all();

        return response()->json([
            'settings' => $settings
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|in:privacy,notifications',
            'name' => 'required|string',
            'display_name' => 'required|string',
            'description' => 'nullable|string',
            'default_value' => 'required|string',
            'type' => 'required|string|in:checkbox,text,number,date,time,datetime,file,email,phone',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            $dashboardSetting = DashboardSetting::create($request->only([
                'category',
                'name',
                'display_name',
                'description',
                'default_value',
            ]));

            DB::commit();
            return response()->json([
                'message' => 'Setting created successfully',
                'setting' => $dashboardSetting
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
