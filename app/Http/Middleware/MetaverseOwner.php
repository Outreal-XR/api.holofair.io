<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MetaverseOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //check if the user is the owner of the metaverse
        $metaverseId = $request->route('metaverse_id') ?? $request->route('id');
        $metaverse = \App\Models\Metaverse::findOrFail($metaverseId);

        if (!$metaverse->isOwner()) {
            return response()->json([
                'message' => 'Only the owner of the metaverse can perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
