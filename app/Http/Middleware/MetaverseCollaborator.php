<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Termwind\Components\Dd;

class MetaverseCollaborator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //check if the user is a collaborator or owner of the metaverse
        $metaverseId = $request->route('metaverse_id') ?? $request->route('id');

        $metaverse = \App\Models\Metaverse::findOrFail($metaverseId);

        if (!$metaverse->canUpdateMetaverse()) {
            return response()->json([
                'message' => 'You are not allowed to perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
