<?php

namespace App\Http\Controllers;

use App\Http\Resources\MetaverseResource;
use App\Models\Metaverse;
use App\Models\Template;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $metaverses = Metaverse::where('name', 'NOT LIKE', '%Blank%')->whereHas("template")->with("template")->orderBy("created_at", "DESC");
        $total = $metaverses->count();

        if ($request->has("limit")) {
            $metaverses = $metaverses->limit($request->limit);
        }

        $metaverses = $metaverses->get();

        return response()->json([

            "data" => [
                "metaverses" => MetaverseResource::collection($metaverses),
                "total" => $total
            ]
        ], Response::HTTP_OK);
    }
}
