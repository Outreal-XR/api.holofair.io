<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\MetaverseTrait;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use MetaverseTrait;
    //sign up
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number' => 'unique:users,phone_number|nullable',
            'email' => 'required|string|unique:users',
            'password' => 'required|string|min:8|confirmed|different:username',
            'template_id' => 'integer|exists:templates,id|nullable',
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

        //if template_id is provided, create a metaverse from the template
        $metaverse = null;
        if ($request->has('template_id')) {
            try {
                //generate unique metaverse name
                $metaverseName = $this->generateMetaverseName($user->first_name, $user->last_name);
                $request->merge(['name' => $metaverseName]);
                $metaverse = $this->createNewMetaverse($request);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], $e->getCode());
            }
        }

        return response()->json([
            'message' => 'User created successfully',
            'access_token' => $user->createToken($user->email)->plainTextToken,
            'metaverse' => $metaverse
        ], 200);
    }

    //login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|exists:users',
            'password' => 'required|string|min:8',
            'template_id' => 'integer|exists:templates,id|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            //if template_id is provided, create a metaverse from the template
            $metaverse = null;
            if ($request->has('template_id')) {
                try {
                    //generate unique metaverse name
                    $metaverseName = $this->generateMetaverseName($user->first_name, $user->last_name);
                    $request->merge(['name' => $metaverseName]);
                    $metaverse = $this->createNewMetaverse($request);
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => $e->getMessage()
                    ], $e->getCode());
                }
            }

            try {

                Auth::login($user);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage()
                ], $e->getCode());
            }

            return response()->json([
                'message' => 'Logged in successfully',
                'access_token' => $user->createToken($user->email)->plainTextToken,
                'metaverse' => $metaverse
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid credentials, please try again!'
            ], 401);
        }
    }

    public function user(Request $request)
    {
        //user with roles names and permissions names
        $user = $request->user()->load('roles:name', 'permissions:name');
        return response()->json([
            'message' => 'User retrieved successfully',
            'user' => [
                ...$user->toArray(),
                'avatar' => asset($user->avatar),
                'isVerified' => $user->hasVerifiedEmail(),
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'subscriptionPlan' => $user->plan()->with(['subscriptions' => function ($query) {
                    $query->where('status', 'paid')->limit(1);
                }])->first(),

                // 'id' => $user->id,
                // 'first_name' => $user->first_name,
                // 'last_name' => $user->last_name,
                // "email" => $user->email,
                // 'avatar' => asset($user->avatar),
                // "uuid" => $user->uuid,
                // "registered_at" => $user->created_at,
                // 'isVerified' => $user->hasVerifiedEmail(),
                // "roles" => $user->getRoleNames(),
                // "permissions" => $user->getAllPermissions()->pluck('name'),
                // 'subscriptionPlan' => $user->subscription()->with('plan')->first(),
            ]
        ], 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'string|nullable',
            'last_name' => 'string|nullable',
            //skip unique validation if email is same as current email
            'email' => 'email|string|nullable|unique:users,email,' . $request->user()->id,
            'current_password' => 'required_with:password|string|min:8|nullable',
            'password' => 'required_with:current_password|string|min:8|confirmed|different:current_password|nullable',
            'password_confirmation' => 'required_with:password|string|min:8|same:password|nullable',
            'avatar' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|nullable',
            'delete_avatar' => 'boolean|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        $sendNotification = false;
        $user = $request->user();

        if ($request->has('first_name') && $request->first_name !== $user->first_name) {
            $user->first_name = $request->first_name;
        }

        if ($request->has('last_name') && $request->last_name !== $user->last_name) {
            $user->last_name = $request->last_name;
        }

        if ($request->has('email') && $request->email !== $user->email) {
            $user->email = strtolower($request->email);
            $user->email_verified_at = null;
            $sendNotification = true;
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

        if ($request->has('delete_avatar') && $request->delete_avatar) {
            if ($user->avatar) {
                $oldAvatar = public_path($user->avatar);
                if (file_exists($oldAvatar)) {
                    unlink($oldAvatar);
                }

                $user->avatar = 'images/avatars/default.jpg';
            }
        }

        $user->save();

        if ($request->has('email') && $sendNotification) {
            $user->sendEmailVerificationNotification();
        }

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
