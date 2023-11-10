<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvitedUserResource;
use App\Http\Resources\UserResource;
use App\Models\InvitedUser;
use App\Models\Metaverse;
use App\Models\User;
use App\Notifications\InviteNotification;
use Brevo\Client\Model\SendSmtpEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MetaverseUserController extends Controller
{
    /**
     * Send invite to user to collaborate on metaverse or view it
     * . For registered users only
     * . Generate token and send it to user email
     * . User can accept or reject the invitation
     * . Viewer have status accepted by default
     * . Every invited user is stored in invited_users table
     * @param Request $request email, role
     * @param string $metaverse_id
     * @return \Illuminate\Http\JsonResponse message
     * @throws \Exception
     */
    public function sendInvite(Request $request, string $metaverse_id)
    {
        $validation = Validator::make($request->all(), [
            "email" => "required|email",
            "role" => "required|string|in:viewer,editor,admin"
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $metaverse = Metaverse::findOrfail($metaverse_id);
        $role = $request->role;

        //authorize user and check if he is allowed to invite
        $is_authorized = $this->authorizeUser($role, $metaverse);
        if ($is_authorized !== true) {
            return $is_authorized;
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                "message" => "User not found"
            ], 404);
        }

        //update or create the invite
        $invited_user = InvitedUser::where('email', $request->email)->where('metaverse_id', $metaverse->id)->first();

        if ($invited_user) {
            return response()->json([
                "message" => "Invite already sent"
            ], 400);
        }

        $email_config = new SendSmtpEmail();
        $email_config->setSender(array('name' => 'Amrullah Mishelov', 'email' => 'mishelov@outrealxr.com'));
        $email_config->setTo(array(array('email' => $request->email, 'name' => $user->fullName())));
        $email_config->setSubject('Invitation to collaborate in ' . $metaverse->name);


        DB::beginTransaction();
        try {
            $invited_user = new InvitedUser();
            $invited_user->metaverse_id = $metaverse->id;
            $invited_user->email = $request->email;
            $invited_user->role = $role;
            $invited_user->invited_by = Auth::id();
            $invited_user->token = time() . Str::random(32);
            $invited_user->token_expiry = $role === 'viewer' ? null : Carbon::now()->addDays(7);
            $invited_user->status = $role === 'viewer' ? 'accepted' : 'pending';
            $invited_user->save();

            $invited_userLink = env('FRONT_URL');
            if ($role === 'viewer') {
                $invited_userLink .= '/p/metaverses?code=' . $metaverse->id;
            } else {
                $invited_userLink .= '/metaverses/' . $metaverse->id . '/invitations/' . $invited_user->id;
            }

            $email_config->setHtmlContent(view('emails.collaborator-invite', [
                'metaverseName' => $metaverse->name,
                'inviterName' => Auth::user()->fullName(),
                'role' => $role,
                "url" => $invited_userLink,
            ])->render());


            // $this->brevoApiInstance->sendTransacEmail($email_config);
            DB::commit();

            return response()->json([
                "message" => "Invite sent successfully"
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Something went wrong",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the role of the invited user
     * @param Request $request role
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function updateInvite(Request $request, string $id)
    {
        $validation = Validator::make($request->all(), [
            "role" => "required|string|in:viewer,editor,admin"
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $invited_user = InvitedUser::with(['user', 'inviter'])->findOrfail($id);

        if ($invited_user->role === $request->role) {
            return response()->json([
                "message" => "Already Invited"
            ], 400);
        }

        $user = $invited_user->user;

        if (!$user) {
            return response()->json([
                "message" => "User not found"
            ], 404);
        }

        $metaverse = $invited_user->metaverse;

        switch ($invited_user->status) {
            case 'accepted':
                if ($invited_user->role === $request->role) {
                    return response()->json([
                        "message" => "Already Invited"
                    ], 400);
                }

                break;
            case 'pending':
                if ($invited_user->token_expiry < Carbon::now()) {
                    return response()->json([
                        "message" => "Invite expired",
                    ], 400);
                }
                break;
        }

        $email_config = new SendSmtpEmail();
        $email_config->setSender(array('name' => 'Amrullah Mishelov', 'email' => 'mishelov@outrealxr.com'));
        $email_config->setTo(array(array('email' => $user->email, 'name' => $user->fullName())));
        $email_config->setSubject('Role updated in ' . $metaverse->name);

        DB::beginTransaction();

        try {

            $invited_user->role = $request->role;
            $invited_user->status = $request->role === 'viewer' ? 'accepted' : 'pending';
            $invited_user->token_expiry = $request->role === 'viewer' ? null : Carbon::now()->addDays(7);
            $invited_user->save();

            $invited_userLink = env('FRONT_URL');
            if ($request->role === 'viewer') {
                $invited_userLink .= '/p/metaverses?code=' . $metaverse->id;
            } else {
                $invited_userLink .= '/metaverses/' . $metaverse->id . '/invitations/' . $invited_user->id;
            }

            $email_config->setHtmlContent(view('emails.invite-update', [
                'metaverseName' => $metaverse->name,
                'inviterName' => Auth::user()->fullName(),
                'role' => $request->role,
                "url" => $invited_userLink,
            ])->render());

            $this->brevoApiInstance->sendTransacEmail($email_config);

            DB::commit();

            return response()->json([
                "message" => "Invite updated successfully",
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Something went wrong. Error:" . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend the invite to the user
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function resendInvite(string $id)
    {
        $invited_user = InvitedUser::findOrfail($id);

        switch ($invited_user->status) {
            case 'accepted':
                return response()->json([
                    "message" => "Invite already accepted"
                ], 400);
                break;
            case 'pending':
                if ($invited_user->token_expiry > Carbon::now()) {
                    return response()->json([
                        "message" => "Invite already sent, and not expired yet",
                    ], 400);
                }
                break;
        }

        $user = $invited_user->user;
        $metaverse = $invited_user->metaverse;

        $email_config = new SendSmtpEmail();
        $email_config->setSender(array('name' => 'Amrullah Mishelov', 'email' => 'mishelov@outrealxr.com'));
        $email_config->setTo(array(array('email' => $user->email, 'name' => $user->fullName())));
        $email_config->setSubject('Invitation to collaborate in ' . $metaverse->name);

        DB::beginTransaction();

        try {

            if ($invited_user->role !== 'viewer') {
                $invited_user->token_expiry = Carbon::now()->addDays(7);

                if ($invited_user->status === 'rejected') {
                    $invited_user->status = 'pending';
                }

                $invited_user->save();
            }

            $invited_userLink = env('FRONT_URL');
            if ($invited_user->role === 'viewer') {
                $invited_userLink .= '/p/metaverses?code=' . $metaverse->id;
            } else {
                $invited_userLink .= '/metaverses/' . $metaverse->id . '/invitations/' . $invited_user->id;
            }

            $email_config->setHtmlContent(view('emails.collaborator-invite', [
                'metaverseName' => $metaverse->name,
                'inviterName' => Auth::user()->fullName(),
                'role' => $invited_user->role,
                "url" => $invited_userLink,
            ])->render());

            $this->brevoApiInstance->sendTransacEmail($email_config);

            DB::commit();

            return response()->json([
                "message" => "Invite sent successfully",
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Something went wrong. Error:" . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the emails of the users that can be invited to the metaverse and that don't include the owner
     * @param Request $request search
     * @param string $id metaverse id
     * @return \Illuminate\Http\JsonResponse Array of emails
     */
    public function searchEmails(Request $request, $id)
    {
        if (!$request->search) {
            return response()->json([
                'message' => 'Please provide a search query',
                'data' => []
            ], 200);
        }

        $metaverse = Metaverse::findorFail($id);
        $blockedUsers = $metaverse->blockedUsers()->pluck('blocked_user_id');

        //remove spaces
        $search = '%' . str_replace(' ', '%', $request->search) . '%';
        $emails = User::where('id', '!=', Auth::id())
            ->where('id', '!=', $metaverse->userid)
            ->whereNotIn('id', $blockedUsers)
            ->where('email', 'LIKE', $search)->pluck('email');

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


    /**
     * Get the collaborators of the metaverse
     * @param string $id metaverse id
     * @return \Illuminate\Http\JsonResponse UserResource owner, Collection of InvitedUserResource 
     */
    public function getCollaborators($id)
    {
        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

        $collaborators = $metaverse->invitedUsers()->orderBy('role', 'asc')->get();
        $owner = $metaverse->user;

        return response()->json([
            "data" => [
                "owner" => UserResource::make($owner),
                "collaborators" => InvitedUserResource::collection($collaborators)
            ]
        ], 200);
    }

    /**
     * Block the user from the metaverse
     * @param int $id invited_user invite id
     * @param int $metaverse_id metaverse id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function blockUser($metaverse_id, $invite_id)
    {
        $metaverse = Metaverse::findOrfail($metaverse_id);

        $invite = InvitedUser::with('user')->where('id', $invite_id)->where('metaverse_id', $metaverse_id)->first();

        if (!$invite) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        if ($invite->status === 'pending') {
            return response()->json([
                "message" => "User must accept the invite first"
            ], 400);
        }

        $user = $invite->user;


        //check if the user is already blocked in the metaverse
        if ($metaverse->blockedusers->contains($user->id)) {
            return response()->json([
                "message" => $user->fullName() . " is already blocked"
            ], 400);
        }

        $metaverse->blockedUsers()->attach($user->id, ['user_id' => Auth::id()]);

        $invite->update(['status' => 'blocked']);

        return response()->json([
            "message" => $user->fullName() . " blocked successfully"
        ], 200);
    }

    /**
     * Unblock the user from the metaverse
     * @param int $id invited_user invite id
     * @param int $metaverse_id metaverse id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function unblockUser($metaverse_id, $invite_id)
    {
        $metaverse = Metaverse::findOrfail($metaverse_id);

        $invite = InvitedUser::with('user')->where('id', $invite_id)->where('metaverse_id', $metaverse_id)->first();

        if (!$invite) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        if ($invite->status !== 'blocked') {
            return response()->json([
                "message" => "User is not blocked"
            ], 400);
        }

        $user = $invite->user;

        //check if the user is already blocked in the metaverse
        if (!$metaverse->blockedusers->contains($user->id)) {
            return response()->json([
                "message" =>  $user->fullName() . " is not blocked"
            ], 400);
        }

        $metaverse->blockedUsers()->detach($user->id);

        $invite->update(['status' => 'accepted']);

        return response()->json([
            "message" =>  $user->fullName() . " unblocked successfully"
        ], 200);
    }

    /**
     * Remove the user from metaverse invited users (either pending or accepted)
     * @param int $id invited_user invite id
     * @param int $metaverse_id metaverse id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function removeUser(Request $request, $metaverse_id, $invite_id)
    {
        $isInvite = boolval($request->isInvite);

        $metaverse = Metaverse::findOrfail($metaverse_id);

        $invite = InvitedUser::where('id', $invite_id)->where('metaverse_id', $metaverse_id)->first();

        if (!$invite) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        $user = $invite->user;
        //is isInvite => remove the pending invites only
        if ($isInvite) {

            if ($invite->status !== 'pending') {
                return response()->json([
                    "message" => "You can't remove " . $user->fullName() . " because he/she already " . $invite->status . " the invite"
                ], 400);
            }

            $invite->delete();

            return response()->json([
                "message" => $user->fullName() . " invite deleted successfully",
            ], 200);
        } else {
            //else remove the accepted/blocked users

            if ($invite->status === 'accepted' || $invite->status === 'blocked') {
                $invite->delete();


                if ($invite->status === 'blocked') {
                    $metaverse->blockedUsers()->detach($user->id);
                }

                return response()->json([
                    "message" =>  $user->fullName() . " removed successfully"
                ], 200);
            }

            return response()->json([
                "message" => "The user must accept the invite first!"
            ], 400);
        }
    }

    /**
     * Authorize the user to send/update the invite
     * @param string $role user role
     * @param Metaverse $metaverse metaverse
     * @return \Illuminate\Http\JsonResponse message
     */
    private function authorizeUser($role, $metaverse)
    {
        switch ($role) {
            case 'admin': {
                    if (!$metaverse->isOwner()) {
                        return response()->json([
                            'message' => 'Only the owner of the metaverse can perform this action.'
                        ], 403);
                    }
                }
                break;
            case 'editor': {
                    if (!$metaverse->canUpdateMetaverse()) {
                        return response()->json([
                            'message' => 'You are not allowed to invite collaborators to this metaverse.'
                        ], 403);
                    }
                }
                break;
            case 'viewer': {
                    if (!$metaverse->canAccessMetaverse()) {
                        return response()->json([
                            'message' => 'You are not allowed to invite viewers to this metaverse.'
                        ], 403);
                    }
                }
                break;
            default: {
                    return response()->json([
                        'message' => 'Invalid role.'
                    ], 403);
                }
        }

        return true;
    }

    public function getPendingInvitations()
    {
        $user = Auth::user();

        $invites = $user->invites()->where('status', 'pending')
            ->where('token_expiry', '>', Carbon::now())
            ->with('metaverse')
            ->get();

        $invitesCount = $invites->count();

        $invites = InvitedUserResource::collection($invites);

        return response()->json([
            'data' => [
                'invites' => $invites,
                'invitesCount' => $invitesCount
            ]
        ], 200);
    }

    public function checkInvite($metaverse_id, $invite_id)
    {
        $validity = $this->checkInviteValidity($invite_id, $metaverse_id);
        $metaverse = Metaverse::findOrFail($metaverse_id);

        return response()->json([
            'message' => $validity['message'],
            'data' => [
                'metaverse' => [
                    'name' => $metaverse->name,
                    'thumbnail' => $metaverse->thumbnail ? asset($metaverse->thumbnail) : null,
                    'user' => [
                        'first_name' => $metaverse->user->first_name,
                        'last_name' => $metaverse->user->last_name,
                    ],
                ],
            ]
        ], $validity['statusCode']);
    }

    public function acceptInvite($metaverse_id, $invite_id)
    {
        $validity = $this->checkInviteValidity($invite_id, $metaverse_id);

        if (!$validity['valid']) {
            return response()->json([
                'message' => $validity['message'],
            ], $validity['statusCode']);
        }


        $invite = InvitedUser::find($invite_id);
        $invite->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Invitation accepted successfully',
        ], 200);
    }

    public function rejectInvite($metaverse_id, $invite_id)
    {
        $validity = $this->checkInviteValidity($invite_id, $metaverse_id);

        if (!$validity['valid']) {
            return response()->json([
                'message' => $validity['message']
            ], $validity['statusCode']);
        }

        $invite = InvitedUser::find($invite_id);
        $invite->delete();

        return response()->json([
            'message' => 'Invitation rejected successfully'
        ], 200);
    }

    private function checkInviteValidity($invite_id, $metaverse_id)
    {
        $validity = [
            'valid' => true,
            'message' => 'Invitation is valid',
            'statusCode' => 200,
        ];

        //check if the invitation is valid
        $invitation = InvitedUser::where('id', $invite_id)->where('metaverse_id', $metaverse_id)->first();

        if (!$invitation) {
            $validity['valid'] = false;
            $validity['message'] = 'Invitation not found';
            $validity['statusCode'] = 404;
            return $validity;
        }

        //check if the invitation belongs to the user
        if ($invitation->email !== Auth::user()->email) {
            $validity['valid'] = false;
            $validity['message'] = 'Invitation not found';
            $validity['statusCode'] = 403;
            return $validity;
        }

        switch ($invitation->status) {
            case 'accepted': {
                    $validity['valid'] = true;
                    $validity['message'] = 'Invitation already accepted';
                    $validity['statusCode'] = 200;
                    return $validity;
                }
                break;
            case 'blocked': {
                    $validity['valid'] = false;
                    $validity['message'] = 'You are blocked from this metaverse';
                    $validity['statusCode'] = 200;
                    return $validity;
                }
                break;
            case 'declined': {
                    $validity['valid'] = false;
                    $validity['message'] = 'Invitation already rejected';
                    $validity['statusCode'] = 400;
                    return $validity;
                }
                break;
            case 'pending': {
                    if ($invitation->token_expiry < Carbon::now()) {
                        $validity['valid'] = false;
                        $validity['message'] = 'Invitation expired';
                        $validity['statusCode'] = 400;
                        return $validity;
                    }
                }
                break;
            default: {
                    $validity['valid'] = false;
                    $validity['message'] = 'Invitation not found';
                    $validity['statusCode'] = 404;
                    return $validity;
                }
        }

        $validatity['invitation'] = $invitation;

        return $validity;
    }
}
