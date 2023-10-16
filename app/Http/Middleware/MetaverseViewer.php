<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MetaverseViewer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //check if the user can access the metaverse
        $metaverseId = $request->route('metaverse_id') ?? $request->route('id');
        $metaverse = \App\Models\Metaverse::findOrFail($metaverseId);

        if (!$metaverse->canAccessMetaverse()) {
            return response()->json([
                "message" => "You are not allowed to access this metaverse"
            ], 403);
        }

        return $next($request);
    }
}
