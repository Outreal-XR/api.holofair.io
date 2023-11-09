<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    //sign up
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number' => 'unique:users,phone_number|nullable',
            'email' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed|different:username'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' =>  $request->has('phone_number') ? $request->phone_number : null,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        event(new Registered($user));

        Auth::login($user);

        return response()->json([
            'message' => 'User created successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    "uuid" => $user->uuid,
                    "registered_at" => $user->created_at,
                    'isVerified' => $user->hasVerifiedEmail(),
                ],
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'token' => $user->createToken($user->email)->plainTextToken
            ]
        ], 200);
    }

    //login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|exists:users',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            return response()->json([
                'message' => 'Logged in successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        "uuid" => $user->uuid,
                        "registered_at" => $user->created_at,
                        'isVerified' => $user->hasVerifiedEmail(),
                    ],
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'token' => $user->createToken($user->email)->plainTextToken
                ]
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid credentials, please try again!'
            ], 401);
        }
    }

    public function user(Request $request)
    {
        return response()->json([
            'message' => 'User retrieved successfully',
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'uuid' => $request->user()->uuid,
                    'registered_at' => $request->user()->created_at,
                    'isVerified' => $request->user()->hasVerifiedEmail(),
                ],
                'roles' => $request->user()->getRoleNames(),
                'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                'token' => $request->user()->createToken($request->user()->email)->plainTextToken
            ]
        ], 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'string|nullable',
            'last_name' => 'string|nullable',
            'email' => 'email|string|unique:users|nullable',
            'current_password' => 'required_with:password|string|min:8|nullable',
            'password' => 'required_with:current_password|string|min:8|confirmed|different:current_password|nullable',
            'password_confirmation' => 'required_with:password|string|min:8|same:password|nullable',
            'avatar' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        $user = $request->user();

        if ($request->has('first_name')) {
            $user->first_name = $request->first_name;
        }

        if ($request->has('last_name')) {
            $user->last_name = $request->last_name;
        }

        if ($request->has('email')) {
            $user->email = strtolower($request->email);
        }

        if ($request->has('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect'
                ], 403);
            }

            $user->password = Hash::make($request->password);
        }

        if ($request->has('avatar')) {
            $imageName = time() . '.' . $request->avatar->extension();
            $request->avatar->move(public_path('images/avatars'), $imageName);
            $path = 'images/avatars/' . $imageName;
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'avatar' => asset($user->avatar)
                ]
            ]
        ], 200);
    }

    public function searchEmails(Request $request)
    {
        if (!$request->search) {
            return response()->json([
                'message' => 'Please provide a search query',
                'data' => []
            ], 200);
        }

        //remove spaces
        $search = str_replace(' ', '', $request->search);

        $emails = User::where('email', 'LIKE', '%' . $search . '%')->pluck('email');

        if ($emails->isEmpty()) {
            return response()->json([
                'message' => 'No emails found',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Emails retrieved successfully',
            'data' => $emails
        ], 200);
    }
}
