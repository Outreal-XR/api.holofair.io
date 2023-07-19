<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailVerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     * @param \Illuminate\Http\Requests\EmailVerificationRequest $request
     * @param Int $id
     * @param String $hash
     *      
     * @return \Illuminate\Http\Response     
     */
    public function verify(EmailVerificationRequest $request, $id, $hash): RedirectResponse
    {
        $user = User::find($id);


        // check if the user exists
        if (!$user) {
            return redirect(env('FRONT_URL') . '/email/verify/invalid');
        }

        // check if the user has provided the correct hash
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect(env('FRONT_URL') . '/email/verify/invalid');
        }

        // check if the user has already verified their email
        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONT_URL') . '/email/verify/already-verified');
        }

        // mark the user's email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect(env('FRONT_URL') . '/email/verify/success');
    }
}
