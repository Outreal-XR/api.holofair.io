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
use App\Models\MetaverseLink;
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
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;

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
            "name" => "required|string|unique:metaverses",
            "thumbnail" => "nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
            // "uuid" => "required|string",
            // "slug" => "required|string",
            "template_id" => "nullable|integer|exists:templates,id",
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
        $total = $metaverses->count();

        if ($request->has("limit")) {
            $metaverses = $metaverses->limit($request->limit);
        }

        $metaverses = $metaverses->get();

        return response()->json([
            "data" =>
            [
                "total" => $total,
                "metaverses" => MetaverseResource::collection($metaverses)
            ]
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
        $metaverse = Metaverse::findOrfail($id);

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

        $metaverse = Metaverse::findOrfail($id);
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
     * Get the shared metaverses with the user
     * @param Request $request limit
     * @return \Illuminate\Http\JsonResponse Collection of MetaverseResource, total shared metaverses
     */
    public function getSharedMetaverses(Request $request)
    {
        //get shared metaverses with roles
        $metaverses = Metaverse::whereHas('invitedUsers', function ($query) {
            $query->where('email', Auth::user()->email)->whereIn('status', ['accepted', 'blocked']);
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

    /**
     * Delete metaverse by id (soft delete)
     * @param string $id metaverse id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function deleteMetaverse(string $id)
    {
        $metaverse = Metaverse::findOrFail($id);

        $metaverse->delete();

        return response()->json([
            "message" => "Metaverse deleted successfully"
        ], 200);
    }

    /**
     * Check if metaverse is unique (case insensitive)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkUniqueness(Request $request)
    {
        $name = $request->name;

        if (!$name) {
            return response()->json([
                "message" => "Name is required"
            ], 400);
        }

        //search for metaverse with the same name (including soft deleted)
        $metaverse = Metaverse::withTrashed()->whereRaw('LOWER(name) = ?', strtolower($name))->first();

        if ($metaverse) {
            return response()->json([
                "isUnique" => false,
                "message" => $metaverse->name . " is already taken"
            ], 200);
        }

        return response()->json([
            "isUnique" => true
        ], 200);
    }

    /**
     * 
     * add link to metaverse
     * @param Request $request
     * @param string $id metaverse id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addLink(Request $request, string $id)
    {
        $validation = Validator::make($request->all(), [
            "url" => "required|url",
            "name" => "required|string",
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => $validation->errors()->first()
            ], 400);
        }

        $metaverse = Metaverse::findOrfail($id);
        $link = MetaverseLink::where('metaverse_id', $metaverse->id)->where(function ($query) use ($request) {
            $query->where('name', $request->name)->orWhere('url', $request->url);
        })->first();

        try {

            if ($link) {
                $link->url = $request->url;
                $link->name = $request->name;
                $link->save();
            } else {
                $link = new MetaverseLink();
                $link->metaverse_id = $metaverse->id;
                $link->name = $request->name;
                $link->url = $request->url;
                $link->save();
            }
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 400);
        }

        return response()->json([
            "message" => "Link added successfully",

        ], 200);
    }
}
