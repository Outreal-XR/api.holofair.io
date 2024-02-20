<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Metaverse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class LanguageController extends Controller
{
    public function addLanguages()
    {
        $languages = [
            [
                'name' => 'English',
                'code' => 'en',
                'native_name' => 'English',
                'rtl' => 0
            ],
            [
                'name' => 'Spanish',
                'code' => 'es',
                'native_name' => 'Español',
                'rtl' => 0
            ],
            [
                'name' => 'French',
                'code' => 'fr',
                'native_name' => 'Français',
                'rtl' => 0
            ],
            [
                'name' => 'German',
                'code' => 'de',
                'native_name' => 'Deutsch',
                'rtl' => 0
            ],
            [
                'name' => 'Russian',
                'code' => 'ru',
                'native_name' => 'Русский',
                'rtl' => 0
            ],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'native_name' => 'العربية',
                'rtl' => 1
            ],
        ];

        DB::beginTransaction();

        try {
            foreach ($languages as $language) {
                Language::create($language);
            }
            DB::commit();
            return response()->json(['message' => 'Languages added successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add languages. Error: ' . $e], 500);
        }
    }

    public function addEnglishToMetaverses()
    {
        $englishLanguage = Language::where('code', 'en')->first();
        $metaverses = Metaverse::all();

        DB::beginTransaction();

        try {
            foreach ($metaverses as $metaverse) {
                $metaverse->languages()->attach($englishLanguage->id);
            }
            DB::commit();
            return response()->json(['message' => 'English added to all metaverses successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add English to metaverses. Error: ' . $e], 500);
        }
    }

    public function index()
    {
        $languages = Language::all();

        return response()->json([
            'languages' => $languages
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:languages',
            'code' => 'required|string|unique:languages',
            'native_name' => 'required|string|unique:languages',
            'rtl' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            $language = Language::create($request->only([
                'name',
                'code',
                'native_name',
                'rtl',
            ]));

            DB::commit();
            return response()->json([
                'message' => 'Language created successfully',
                'language' => $language
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
