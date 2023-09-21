<?php

namespace App\Http\Controllers;

use App\Http\Resources\CollaboratorResource;
use App\Http\Resources\InvitedUserResource;
use App\Http\Resources\MetaverseResource;
use App\Http\Resources\UserResource;
use App\Models\Addressable;
use App\Models\Collaborator;
use App\Models\InvitedUser;
use App\Models\ItemPerRoom;
use App\Models\Metaverse;
use App\Models\Platform;
use App\Models\Template;
use App\Models\User;
use App\Models\VariablePerItem;
use App\Traits\MediaTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MetaverseController extends Controller
{
    use MediaTrait;

    /**
     * Create a new metaverse (wether from template or blank | blank metaverses are also from template (blank template)) 
     * and assign addressables, items per room, variables per item to it
     * @author Asmaa Hamid
     * @param Request $request name, thumbnail, template_id
     * @return \Illuminate\Http\JsonResponse  MetaverseResource metaverse data
     * @throws \Exception
     * 
     * @todo delete the line that adds default addressables to the metaverse after adding the default addressables to the blank template
     */
    public function createMetaverseFromTemplate(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "name" => "required|string",
            "thumbnail" => "nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
            // "uuid" => "required|string",
            // "slug" => "required|string",
            "template_id" => "nullable|integer",
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        if ($request->has("template_id")) {

            $template = Template::find($request->template_id);
            if (!$template) {
                return response()->json([
                    "message" => "Template not found"
                ], 404);
            }
        } else {
            //blank template
            $template = Template::whereHas('metaverse', function ($query) {
                $query->where('name', 'LIKE', '%Blank%');
            })->first();
        }

        //start transaction
        DB::beginTransaction();

        try {
            /* Step-1: add metaverse */
            $thumbnail = null;
            if ($request->has('thumbnail') && !empty($request->thumbnail)) {

                $thumbnail = $this->uploadMedia($request->thumbnail, "images/metaverses/thumbnails/" . Auth::id());
            }

            /*to be sent from unity */
            $uuid = Str::uuid("uuid")->toString();
            $slug = Str::slug($uuid);

            $newMetaverse = Metaverse::create([
                'userid' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description ?? null,
                'uuid' => $uuid,
                'slug' => $slug,
                'url' => $template->metaverse->url,
                'thumbnail' => $thumbnail,
            ]);
            /* end step-1 */

            /* Step-2: add addressables */

            //get addressables from template metaversid
            $templateAddressables = $template->metaverse->addressables;

            //if no addressables found, add addressables of ids [1, 3] (TODO: Delete this after adding addressables to all metaverses)
            if ($templateAddressables->count() == 0) {
                $templateAddressables = Addressable::whereIn('id', [1, 3])->get();
            }

            //attach addressables to new metaverse
            foreach ($templateAddressables as $templateAddressable) {
                $newMetaverse->addressables()->attach($templateAddressable->id);
            }

            /* end step-2 */

            /* Step-3: add items per room */

            //get all the records that the room of which starts with template metaverseid
            $templateItemsPerRoom = ItemPerRoom::where('room', 'LIKE', $template->metaverseid . '%')->get();

            //insert the records with the new metaverseid
            foreach ($templateItemsPerRoom as $item) {
                ItemPerRoom::create([
                    'room' => Str::replaceFirst($template->metaverseid, $newMetaverse->id, $item->room),
                    'guid' => $item->guid,
                    'position' => $item->position,
                    'rotation' => $item->rotation,
                    'scale' => $item->scale,
                ]);
            }
            /* end step-3 */

            /* Step-4: add variables per item */

            //get all the records that the room of which starts with template metaverseid
            $templateVariablesPerItem = VariablePerItem::where('room', 'LIKE', $template->metaverseid . '%')->get();

            //insert the records with the new metaverseid
            foreach ($templateVariablesPerItem as $item) {
                VariablePerItem::create([
                    'room' => Str::replaceFirst($template->metaverseid, $newMetaverse->id, $item->room),
                    'guid' => $item->guid,
                    'key' => $item->key,
                    'value' => $item->value,
                ]);
            }

            /* end step-4 */

            DB::commit();

            return response()->json([
                "message" => "Metaverse created successfully",
                "metaverse" => MetaverseResource::make($newMetaverse)
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            //remove thumbnail
            if ($request->has("thumbnail")) {
                $file = end(explode("/", $thumbnail));
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            return response()->json([
                "message" => "Something went wrong, could not create metaverse",
                "error" => [
                    "message" => $e->getMessage(),
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                ]
            ], 500);
        }
    }

    /**
     * Get metaverse addressables urls by metaverse id and platform id
     * @author Asmaa Hamid
     * @param int $metaverse_id
     * @param int $platform_id
     * @return \Illuminate\Http\JsonResponse => Array addressables urls
     * @throws \Exception
     */
    public function getMetaverseAddrassablesLinks($metaverse_id, $platform_id)
    {
        $metaverse = Metaverse::find($metaverse_id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

        $platform = Platform::find($platform_id);

        if (!$platform) {
            return response()->json([
                "message" => "Platform not found"
            ], 404);
        }

        $addressablesIds = $metaverse->addressables->pluck('id')->toArray();

        $addressablesUrls = $platform->addressables()->whereIn('addressableid', $addressablesIds)->pluck('url')->toArray();

        return response()->json([
            "addressables" => $addressablesUrls
        ], 200);
    }


    /**
     * Get metaverses of the logged in user
     * @param Request $request limit
     * @return \Illuminate\Http\JsonResponse: Collection of MetaverseResource
     * @throws \Exception
     */
    public function getMetaversesByUser(Request $request)
    {

        $metaverses = Metaverse::where('userid', Auth::id())->orderBy('created_at', 'desc');

        if ($request->has("limit")) {
            $metaverses = $metaverses->limit($request->limit);
        }

        $metaverses = $metaverses->get();

        return response()->json([
            "data" => MetaverseResource::collection($metaverses)
        ], 200);
    }

    /**
     * Get metaverse by id
     * @param int $id metaverse id
     * @return \Illuminate\Http\JsonResponse MetaverseResource
     * @throws \Exception
     */
    public function getMetaverseById($id)
    {
        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

        //check permissions
        $is_collaborator = $metaverse
            ->invitedUsers()
            ->where('email', Auth::user()->email)
            ->where('status', 'accepted')
            ->exists();

        if ($metaverse->userid != Auth::id() && !$is_collaborator) {
            return response()->json([
                "message" => "You are not allowed to access this metaverse"
            ], 403);
        }

        return response()->json([
            "data" => MetaverseResource::make($metaverse)
        ], 200);
    }

    /**
     * Update metaverse
     * @param Request $request data to update
     * @param string $id metaverse id
     * @return \Illuminate\Http\JsonResponse: MetaverseResource
     * @throws \Exception
     */
    public function updateMetaverse(Request $request, string $id)
    {

        $validation = Validator::make($request->all(), [
            "thumbnail" => "nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

        if ($request->has('thumbnail') && !empty($request->thumbnail)) {

            $thumbnail = $this->uploadMedia($request->thumbnail, "images/metaverses/thumbnails/" . Auth::id());

            //remove old thumbnail
            if ($metaverse->thumbnail) {
                if (File::exists(public_path($metaverse->thumbnail))) {
                    File::delete(public_path($metaverse->thumbnail));
                }
            }

            $metaverse->thumbnail = $thumbnail;
        }

        if ($request->has('name')) {
            $metaverse->name = $request->name;
        }

        if ($request->has('description')) {
            $metaverse->description = $request->description;
        }

        $metaverse->save();

        return response()->json([
            "message" => "Metaverse updated successfully",
            "metaverse" => MetaverseResource::make($metaverse)
        ], 200);
    }

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

        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

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
            //if already accepted and the role is different, update the role
            if ($invited_user->status === 'accepted' && $invited_user->role !== $request->role) {
                $invited_user->role = $request->role;
                $invited_user->token = time() . Str::random(40);
                $invited_user->token_expiry = $invited_user->role === 'viewer' ? Carbon::now()->addHours(24) : null;
                $invited_user->save();

                return response()->json([
                    "message" => "Invite updated successfully",
                ], 200);
            }

            //if already accepted and the role is the same, return 
            if ($invited_user->status === 'accepted' && $invited_user->role === $request->role) {
                return response()->json([
                    "message" => "Invite already accepted",
                ], 200);
            }

            //if already pending
            if ($invited_user->status === 'pending') {
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

            //if already rejected, update the token
            if ($invited_user->status === 'rejected') {
                $invited_user->role = $request->role;
                $invited_user->status = 'pending';
                $invited_user->token = time() . Str::random(40);
                $invited_user->token_expiry = Carbon::now()->addHours(24);
                $invited_user->save();

                return response()->json([
                    "message" => "Invite sent again",
                ], 200);
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

        $invited_user = InvitedUser::find($id);

        if (!$invited_user) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        if ($invited_user->status === 'pending' && $invited_user->token_expiry < Carbon::now()) {
            return response()->json([
                "message" => "Invite expired"
            ], 400);
        }

        if ($invited_user->status === 'accepted' && $invited_user->role === $request->role) {
            return response()->json([
                "message" => "Already Invited"
            ], 400);
        }
        if ($invited_user->status === 'rejected') {

            return response()->json([
                "message" => "Invite already rejected",
            ], 400);
        }


        $invited_user->role = $request->role;
        $invited_user->status = $request->role === 'viewer' ? 'accepted' : 'pending';
        $invited_user->token = time() . Str::random(40);
        $invited_user->token_expiry = $request->role === 'viewer' ? null : Carbon::now()->addHours(24);
        $invited_user->save();

        return response()->json([
            "message" => "Invite updated successfully",
            'data' => InvitedUserResource::make($invited_user)
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
     * Get the shared metaverses with the user
     * @param Request $request limit
     * @return \Illuminate\Http\JsonResponse Collection of MetaverseResource, total shared metaverses
     */
    public function getSharedMetaverses(Request $request)
    {
        //get shared metaverses with roles
        $metaverses = Metaverse::whereHas('invitedUsers', function ($query) {
            $query->where('email', Auth::user()->email)->where('status', 'accepted');
        })
            ->with(['invitedUsers' => function ($query) {
                $query->where('email', Auth::user()->email)->where('status', 'accepted');
            }])
            ->orderBy('created_at', 'desc');

        $total = $metaverses->count();

        if ($request->has("limit")) {
            $metaverses = $metaverses->limit($request->limit);
        }

        $metaverses = $metaverses->get();


        return response()->json([
            "data" => [
                "metaverses" =>
                MetaverseResource::collection($metaverses),
                "total" => $total
            ]
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

        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                'message' => 'Metaverse not found',
                'data' => []
            ], 404);
        }

        //remove spaces
        $search = str_replace(' ', '', $request->search);
        $emails = User::where('id', '!=', Auth::id())
            ->where('id', '!=', $metaverse->user_id)
            ->where('email', 'LIKE', '%' . $search . '%')->pluck('email');

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
     * Resend the invite to the user
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function resendInvite(string $id)
    {
        $invitation = InvitedUser::find($id);

        if (!$invitation) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        if (
            $invitation->status === 'accepted'
        ) {
            return response()->json([
                "message" => "Invite already accepted"
            ], 400);
        }

        if (($invitation->status === 'pending' && $invitation->token_expiry < Carbon::now())) {
            return response()->json([
                "message" => "Invite already sent",

            ], 400);
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
            'data' => InvitedUserResource::make($invitation)
        ], 200);
    }

    /**
     * Delete the invite
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function deleteInvite(string $id)
    {
        $invitation = InvitedUser::find($id);

        if (!$invitation) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        $invitation->delete();

        return response()->json([
            "message" => "Collaborator removed successfully"
        ], 200);
    }

    /**
     * Block the user from the metaverse
     * @param string $id invited_user invite id
     * @return \Illuminate\Http\JsonResponse message
     */
    public function blockUser(string $id)
    {
    }
}
