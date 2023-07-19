<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::whereHas("metaverse", function ($q) {
            return $q->where("name", "!=", "Blank");
        })
            ->with('metaverse')->get();

        $templates->map(function ($template) {
            $template->metaverse->thumbnail = $template->metaverse->thumbnail ? asset($template->metaverse->thumbnail) : asset("images/metaverses/thumbnails/default/default.jpg");
            return $template;
        });

        return response()->json([
            "message" => "success",
            "data" => $templates
        ], Response::HTTP_OK);
    }
}
