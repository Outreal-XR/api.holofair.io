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

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                "message" => "User not found"
            ], 404);
        }

        if ($metaverse->userid !== Auth::id()) {
            //send a link to the user to view the metaverse

            return response()->json([
                "message" => "Link has been sent",
            ], 200);
        }

        //update or create the invite
        $invited_user = InvitedUser::where('email', $request->email)->where('metaverse_id', $metaverse->id)->first();

        if (!$invited_user) {
            $invitation = new InvitedUser();
            $invitation->metaverse_id = $metaverse->id;
            $invitation->email = $request->email;
            $invitation->role = $request->role;
            $invitation->invited_by = Auth::id();
            $invitation->token = time() . Str::random(40);
            $invitation->token_expiry = $request->role === 'viewer' ? null : Carbon::now()->addHours(24);
            $invitation->status = $request->role === 'viewer' ? 'accepted' : 'pending';
            $invitation->save();

            //send email
            return response()->json([
                "message" => "Invite sent successfully",
            ], 200);
        } else {

            switch ($invited_user->status) {
                case 'accepted': {
                        //if already accepted and the role is different, update the role
                        if ($invited_user->role !== $request->role) {
                            $invited_user->role = $request->role;
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
                        $invited_user->role = $request->role;
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
                        if ($invited_user->role !== $request->role) {
                            $invited_user->role = $request->role;
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
     * Delete the invite
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function deleteInvite(string $id)
    {
        $invitation = InvitedUser::findOrfail($id);

        $invitation->delete();

        return response()->json([
            "message" => "Collaborator removed successfully"
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
        $blockedUsers = $metaverse->blockedUsers()->pluck('email');

        //remove spaces
        $search = str_replace(' ', '', $request->search);
        $emails = User::where('id', '!=', Auth::id())
            ->where('id', '!=', $metaverse->user_id)
            ->where('email', 'LIKE', '%' . $search . '%')->pluck('email');

        $emails = $emails->diff($blockedUsers);

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

        //check if the user is the owner/editor of the metaverse
        if (!$metaverse->canUpdateMetaverse()) {
            return response()->json([
                "message" => "You don't have permission to block users"
            ], 403);
        }

        $invite = InvitedUser::where('id', $invite_id)->where('metaverse_id', $metaverse_id)->first();

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

        $user = User::where('email', $invite->email)->first();

        //check if the user is already blocked in the metaverse
        if ($metaverse->blockedusers->contains($user->id)) {
            return response()->json([
                "message" => "User already blocked"
            ], 400);
        }

        $metaverse->blockedUsers()->attach($user->id, ['user_id' => Auth::id()]);

        $invite->update(['status' => 'blocked']);

        return response()->json([
            "message" => "User blocked successfully"
        ], 200);
    }

    /**
     * Remove the user from metaverse invited users (accepted)
     * @param int $id invited_user invite id
     * @param int $metaverse_id metaverse id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function removeUser($metaverse_id, $invite_id)
    {
        $metaverse = Metaverse::findOrfail($metaverse_id);

        //check if the user is the owner/editor of the metaverse
        if (!$metaverse->canUpdateMetaverse()) {
            return response()->json([
                "message" => "You don't have permission to remove users"
            ], 403);
        }

        $invite = InvitedUser::where('id', $invite_id)->where('metaverse_id', $metaverse_id)->first();

        if (!$invite) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        if ($invite->status === 'accepted' || $invite->status === 'blocked') {
            $invite->delete();

            if ($invite->status === 'blocked') {
                $user = User::where('email', $invite->email)->first();
                $metaverse->blockedUsers()->detach($user->id);
            }


            return response()->json([
                "message" => "User removed successfully"
            ], 200);
        }

        return response()->json([
            "message" => "The user must accept the invite first"
        ], 200);
    }
}
