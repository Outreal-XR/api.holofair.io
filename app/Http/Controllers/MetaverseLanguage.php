<?php

namespace App\Http\Controllers;

use App\Models\Metaverse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class MetaverseLanguage extends Controller
{
    public function index($metaverseId)
    {
        $metaverse = Metaverse::find($metaverseId);
        $languages = $metaverse->languages;
        return response()->json([
            'languages' => $languages
        ]);
    }

    public function create(Request $request, $metaverseId)
    {
        $validator = Validator::make($request->all(), [
            'language_id' => 'required|integer|exists:languages,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        $languageId = $request->language_id;
        $metaverse = Metaverse::find($metaverseId);
        if ($metaverse->languages->contains($languageId)) {
            return response()->json([
                'message' => 'Language already added to metaverse'
            ]);
        }

        $metaverse->languages()->attach($languageId);
        return response()->json([
            'message' => 'Language added to metaverse'
        ]);
    }
}
