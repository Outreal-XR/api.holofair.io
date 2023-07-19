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
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'No temps found',
            'data' => []
        ], Response::HTTP_OK);
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
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
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
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Temp created successfully',
            'data' => $temp,
        ], Response::HTTP_CREATED);
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
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Temp found',
            'data' => [
                'id' => $temp->id,
                'name' => $temp->name,
                'thumbnail' => asset($temp->thumbnail),
                'description' => $temp->description
            ],
        ], Response::HTTP_OK);
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
            return response()->json($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        $temp = Temp::find($id);

        if (!$temp) {
            return response()->json([
                'message' => 'Temp not found',
            ], Response::HTTP_NOT_FOUND);
        }

        if (Auth::id() !== $temp->user_id) {
            return response()->json([
                'message' => 'You cannot edit this temp',
            ], Response::HTTP_UNAUTHORIZED);
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
        ], Response::HTTP_OK);
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
            ], Response::HTTP_NOT_FOUND);
        }

        if (Auth::id() !== $temp->user_id) {
            return response()->json([
                'message' => 'You cannot delete this temp',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $prevImage = $temp->thumbnail;
        if (file_exists($prevImage)) {
            unlink($prevImage);
        }

        $temp->delete();

        return response()->json([
            'message' => 'Temp deleted successfully',
        ], Response::HTTP_OK);
    }
}
