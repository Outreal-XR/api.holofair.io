<?php

namespace App\Http\Controllers;

use App\Models\Temp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class TempController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $temps = Temp::where('user_id', Auth::id())->get();

        if ($temps->isNotEmpty()) {
            return response()->json([
                'data' =>
                $temps->map(function ($temp) {
                    return [
                        'id' => $temp->id,
                        'name' => $temp->name,
                        'thumbnail' => asset($temp->thumbnail),
                        'description' => $temp->description
                    ];
                })
            ], 200);
        }

        return response()->json([
            'message' => 'No temps found',
            'data' => []
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        $imageName = time() . '.' . $request->thumbnail->extension();
        $request->thumbnail->move(public_path('images/temps/thumbnails'), $imageName);
        $path = 'images/temps/thumbnails/' . $imageName;

        $temp = Temp::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'thumbnail' => $path,
            'description' => $request->description,
        ]);

        if (!$temp) {
            return response()->json([
                'message' => 'An error occurred while creating temp',
            ], 500);
        }

        return response()->json([
            'message' => 'Temp created successfully',
            'data' => $temp,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $temp = Temp::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$temp) {
            return response()->json([
                'message' => 'Temp not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Temp found',
            'data' => [
                'id' => $temp->id,
                'name' => $temp->name,
                'thumbnail' => asset($temp->thumbnail),
                'description' => $temp->description
            ],
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'thumbnail' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        $temp = Temp::find($id);

        if (!$temp) {
            return response()->json([
                'message' => 'Temp not found',
            ], 404);
        }

        if (Auth::id() !== $temp->user_id) {
            return response()->json([
                'message' => 'You cannot edit this temp',
            ], 403);
        }

        $data = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        if ($request->thumbnail) {
            $prevImage = $temp->thumbnail;
            if (file_exists($prevImage)) {
                unlink($prevImage);
            }

            $imageName = time() . '.' . $request->thumbnail->extension();
            $request->thumbnail->move(public_path('images/temps/thumbnails'), $imageName);
            $path = 'images/temps/thumbnails/' . $imageName;

            $data['thumbnail'] = $path;
        }

        $temp->update($data);

        return response()->json([
            'message' => 'Temp updated successfully',
            'data' => $temp,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $temp = Temp::find($id);

        if (!$temp) {
            return response()->json([
                'message' => 'Temp not found',
            ], 404);
        }

        if (Auth::id() !== $temp->user_id) {
            return response()->json([
                'message' => 'You cannot delete this temp',
            ], 403);
        }

        $prevImage = $temp->thumbnail;
        if (file_exists($prevImage)) {
            unlink($prevImage);
        }

        $temp->delete();

        return response()->json([
            'message' => 'Temp deleted successfully',
        ], 200);
    }
}
