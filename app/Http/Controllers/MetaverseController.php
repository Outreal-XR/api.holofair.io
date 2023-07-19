<?php

namespace App\Http\Controllers;

use App\Models\ItemPerRoom;
use App\Models\Metaverse;
use App\Models\Platform;
use App\Models\Template;
use App\Models\VariablePerItem;
use App\Models\VariablePerRoom;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
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
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($request->has("template_id")) {

            $template = Template::find($request->template_id);
            if (!$template) {
                return response()->json([
                    "message" => "Template not found"
                ], Response::HTTP_NOT_FOUND);
            }
        } else {
            //blank template
            $template = Template::whereHas('metaverse', function ($query) {
                $query->where('name', 'Blank');
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
                "metaverse" => $newMetaverse
            ], Response::HTTP_CREATED);
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
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getMetaverseAddrassables(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "uuid" => "required|string",
            "platformid" => "required|integer",
        ]);

        if ($validation->fails()) {
            return response()->json([
                "message" => "Validation error",
                "errors" => $validation->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $metaverse = Metaverse::where('uuid', $request->uuid)->first();

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], Response::HTTP_NOT_FOUND);
        }

        $platform = Platform::find($request->platformid);

        if (!$platform) {
            return response()->json([
                "message" => "Platform not found"
            ], Response::HTTP_NOT_FOUND);
        }

        $addressablesIds = $metaverse->addressables->pluck('id')->toArray();

        $addressablesUrls = $platform->addressables()->whereIn('addressableid', $addressablesIds)->pluck('url')->toArray();

        return response()->json([
            "addressables" => $addressablesUrls
        ], Response::HTTP_OK);
    }

    public function getMetaversesByUser()
    {
        $metaverses = Metaverse::where('userid', Auth::id())->get();

        return response()->json([
            "data" => $metaverses
        ], Response::HTTP_OK);
    }

    public function getMetaverseById($id)
    {
        $metaverse = Metaverse::find($id);

        if (!$metaverse) {
            return response()->json([
                "message" => "Metaverse not found"
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            "data" => $metaverse
        ], Response::HTTP_OK);
    }
}
