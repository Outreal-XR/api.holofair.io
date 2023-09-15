<?php

namespace App\Http\Controllers;

use App\Http\Resources\CollaboratorResource;
use App\Http\Resources\MetaverseResource;
use App\Http\Resources\UserResource;
use App\Models\Collaborator;
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

    public function getMetaverseById($id)
    {
        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

        return response()->json([
            "data" => MetaverseResource::make($metaverse)
        ], 200);
    }

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
        $collaborator = Collaborator::where('email', $request->email)->where('metaverse_id', $metaverse->id)->first();

        if (!$collaborator) {
            $invitation = new Collaborator();
            $invitation->metaverse_id = $metaverse->id;
            $invitation->email = $request->email;
            $invitation->role = $request->role;
            $invitation->invited_by = Auth::id();
            $invitation->token = time() . Str::random(40);
            $invitation->token_expiry = Carbon::now()->addHours(24);
            $invitation->save();

            //send email

            return response()->json([
                "message" => "Invite sent successfully",
            ], 200);
        } else {
            //if already accepted and the role is different, update the role
            if ($collaborator->status === 'accepted' && $collaborator->role !== $request->role) {
                $collaborator->role = $request->role;
                $collaborator->save();

                return response()->json([
                    "message" => "Invite updated successfully",
                ], 200);
            }

            //if already accepted and the role is the same, return 
            if ($collaborator->status === 'accepted' && $collaborator->role === $request->role) {
                return response()->json([
                    "message" => "Invite already accepted",
                ], 200);
            }

            //if already pending
            if ($collaborator->status === 'pending') {
                //if the role is different, update the role
                if ($collaborator->role !== $request->role) {
                    $collaborator->role = $request->role;
                }

                //if the token expired, update the token
                if ($collaborator->token_expiry < Carbon::now()) {
                    $collaborator->token = time() . Str::random(40);
                    $collaborator->token_expiry = Carbon::now()->addHours(24);
                }

                $collaborator->save();
                return response()->json([
                    "message" => "Invite updated successfully",
                ], 200);
            }

            //if already rejected, update the token
            if ($collaborator->status === 'rejected') {
                $collaborator->role = $request->role;
                $collaborator->status = 'pending';
                $collaborator->token = time() . Str::random(40);
                $collaborator->token_expiry = Carbon::now()->addHours(24);
                $collaborator->save();

                return response()->json([
                    "message" => "Invite sent again",
                ], 200);
            }
        }
    }


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

        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json([
                "message" => "Invite not found"
            ], 404);
        }

        if ($collaborator->status === 'pending' && $collaborator->token_expiry < Carbon::now()) {
            return response()->json([
                "message" => "Invite expired"
            ], 400);
        }

        if ($collaborator->status === 'accepted' && $collaborator->role === $request->role) {
            return response()->json([
                "message" => "Already collaborator"
            ], 400);
        }
        if ($collaborator->status === 'rejected') {

            return response()->json([
                "message" => "Invite already rejected",
            ], 400);
        }


        $collaborator->role = $request->role;
        $collaborator->save();

        return response()->json([
            "message" => "Invite updated successfully",
            'data' => CollaboratorResource::make($collaborator)
        ], 200);
    }

    public function getCollaborators($id)
    {
        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], 404);
        }

        $collaborators = $metaverse->collaborators()->orderBy('role', 'asc')->get();
        $owner = $metaverse->user;

        return response()->json([
            "data" => [
                "owner" => UserResource::make($owner),
                "collaborators" => CollaboratorResource::collection($collaborators)
            ]
        ], 200);
    }

    public function getSharedMetaverses(Request $request)
    {
        $metaverses = Metaverse::whereHas('collaborators', function ($query) {
            $query->where('email', Auth::user()->email)->where('status', 'accepted');
        })->orderBy('created_at', 'desc');

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

    public function resendInvite(string $id)
    {
        $invitation = Collaborator::find($id);

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

        $invitation->token = time() . Str::random(40);
        $invitation->token_expiry = Carbon::now()->addHours(24);

        if ($invitation->status === 'rejected') {
            $invitation->status = 'pending';
        }

        $invitation->save();

        return response()->json([
            "message" => "Invite sent successfully",
            'data' => CollaboratorResource::make($invitation)
        ], 200);
    }

    public function deleteInvite(string $id)
    {
        $invitation = Collaborator::find($id);

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
}
