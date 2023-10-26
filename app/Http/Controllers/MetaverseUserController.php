<?php

namespace App\Http\Controllers;

use App\Http\Resources\InvitedUserResource;
use App\Http\Resources\UserResource;
use App\Models\InvitedUser;
use App\Models\Metaverse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * @param string $id metaverse id
     * @return \Illuminate\Http\JsonResponse message
     * @throws \Exception
     */
    public function sendInvite(Request $request, string $id)
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

        $metaverse = Metaverse::findOrfail($id);
        $role = $request->role;

        //authorize
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

        if (!$invited_user) {
            $invitation = new InvitedUser();
            $invitation->metaverse_id = $metaverse->id;
            $invitation->email = $request->email;
            $invitation->role = $role;
            $invitation->invited_by = Auth::id();
            $invitation->token = time() . Str::random(40);
            $invitation->token_expiry = $role === 'viewer' ? null : Carbon::now()->addHours(24);
            $invitation->status = $role === 'viewer' ? 'accepted' : 'pending';
            $invitation->save();

            //send email
            return response()->json([
                "message" => "Invite sent successfully",
            ], 200);
        } else {

            switch ($invited_user->status) {
                case 'accepted': {
                        //if already accepted and the role is different, update the role
                        if ($invited_user->role !== $role) {
                            $invited_user->role = $role;
                            $invited_user->token = time() . Str::random(40);
                            $invited_user->token_expiry = $invited_user->role === 'viewer' ? Carbon::now()->addHours(24) : null;
                            $invited_user->save();

                            return response()->json([
                                "message" => "Invite updated successfully",
                            ], 200);
                        } else {
                            //if already accepted and the role is the same, return 
                            return response()->json([
                                "message" => "Invite already accepted",
                            ], 200);
                        }
                    }
                    break;
                case 'rejected': {
                        //if already rejected, update the token
                        $invited_user->role = $role;
                        $invited_user->status = 'pending';
                        $invited_user->token = time() . Str::random(40);
                        $invited_user->token_expiry = Carbon::now()->addHours(24);
                        $invited_user->save();

                        return response()->json([
                            "message" => "Invite sent again",
                        ], 200);
                    }
                    break;
                case 'pending': {
                        //if the role is different, update the role
                        if ($invited_user->role !== $role) {
                            $invited_user->role = $role;
                        }

                        //if the token expired, update the token
                        if ($invited_user->token_expiry < Carbon::now()) {
                            $invited_user->token = time() . Str::random(40);
                            $invited_user->token_expiry = Carbon::now()->addHours(24);
                        }

                        $invited_user->save();
                        return response()->json([
                            "message" => "Invite updated successfully",
                        ], 200);
                    }
                    break;
            }
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
            "role" => "string|in:viewer,editor,admin"
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $invited_user = InvitedUser::with(['user', 'inviter'])->findOrfail($id);

        $metaverse = $invited_user->metaverse;

        if (!$metaverse->canUpdateMetaverse()) {
            return response()->json([
                "message" => "You are not authorized to update this metaverse"
            ], 403);
        }

        switch ($invited_user->status) {
            case 'accepted':
                if ($invited_user->role === $request->role) {
                    return response()->json([
                        "message" => "Already Invited"
                    ], 400);
                }

                break;
            case 'rejected':
                return response()->json([
                    "message" => "Invite already rejected",
                ], 400);
                break;
            case 'pending':
                if ($invited_user->token_expiry < Carbon::now()) {
                    return response()->json([
                        "message" => "Invite expired",
                    ], 400);
                }
                break;
        }

        $invited_user->role = $request->role;
        $invited_user->status = $request->role === 'viewer' ? 'accepted' : 'pending';
        $invited_user->token = time() . Str::random(40);
        $invited_user->token_expiry = $request->role === 'viewer' ? null : Carbon::now()->addHours(24);
        $invited_user->save();

        return response()->json([
            "message" => "Invite updated successfully",
        ], 200);
    }

    /**
     * Resend the invite to the user
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function resendInvite(string $id)
    {
        $invitation = InvitedUser::findOrfail($id);

        switch ($invitation->status) {
            case 'accepted':
                return response()->json([
                    "message" => "Invite already accepted"
                ], 400);
                break;
            case 'pending':
                if ($invitation->token_expiry > Carbon::now()) {
                    return response()->json([
                        "message" => "Invite already sent",
                    ], 400);
                }
                break;
        }



        if ($invitation->role !== 'viewer') {

            $invitation->token = time() . Str::random(40);
            $invitation->token_expiry = Carbon::now()->addHours(24);

            if ($invitation->status === 'rejected') {
                $invitation->status = 'pending';
            }

            $invitation->save();
        }

        return response()->json([
            "message" => "Invite sent successfully",
        ], 200);
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
     * Remove the user from metaverse invited users (accepted)
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

    
}
