<?php

namespace App\Traits;

use App\Http\Resources\MetaverseResource;
use App\Models\Addressable;
use App\Models\ItemPerRoom;
use App\Models\Metaverse;
use App\Models\Template;
use App\Models\VariablePerItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

trait MetaverseTrait
{
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
    public function createNewMetaverse(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "name" => "required|string|unique:metaverses",
            "thumbnail" => "nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048",
            "template_id" => "nullable|integer|exists:templates,id",
        ]);

        if ($validation->fails()) {
            throw new \Exception($validation->errors()->first(), 400);
        }

        if ($request->has("template_id")) {

            $template = Template::find($request->template_id);
            if (!$template) {
                throw new \Exception("Template not found", 404);
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
            return MetaverseResource::make($newMetaverse);
        } catch (\Exception $e) {
            DB::rollback();

            //remove thumbnail
            if ($request->has("thumbnail")) {
                $file = end(explode("/", $thumbnail));
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            throw new \Exception("Something went wrong, could not create metaverse. Error: " . $e->getMessage(), 500);
        }
    }
}
