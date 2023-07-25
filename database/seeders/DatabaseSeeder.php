<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Metaverse;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $uuid = Str::uuid("uuid")->toString();
        $slug = Str::slug($uuid);
        $metaverse = Metaverse::create([
            "id" => 8319026,
            "userid" => 1,
            "uuid" => $uuid,
            "slug" => $slug,
            "name" => "Metaverse",
            "thumbnail" => "images/metaverses/thumbnails/1/deafult.jpg",
            "description" => "Metaverse Template 1",
            "url" => "https://metaverse.studio/1",

        ]);

        $metaverse->templates()->create();

        //blank template
        $uuid = Str::uuid("uuid")->toString();
        $slug = Str::slug($uuid);
        $metaverse = Metaverse::create([
            "userid" => 1,
            "uuid" => $uuid,
            "slug" => $slug,
            "name" => "Blank",
            "thumbnail" => "images/metaverses/thumbnails/1/deafult.jpg",
            "description" => "Blank Template",
            "url" => "https://metaverse.studio/1",

        ]);

        $metaverse->templates()->create();

        //run sql queries        
        $items_per_room_path = 'app/developer_docs/sql/items_per_room.sql';
        $items_per_room_sql = file_get_contents(base_path($items_per_room_path));
        DB::unprepared($items_per_room_sql);

        $variables_per_room_path = 'app/developer_docs/sql/variables_per_room.sql';
        $variables_per_room_sql = file_get_contents(base_path($variables_per_room_path));
        DB::unprepared($variables_per_room_sql);

        $variables_per_item_path = 'app/developer_docs/sql/variables_per_item.sql';
        $variables_per_item_sql = file_get_contents(base_path($variables_per_item_path));
        DB::unprepared($variables_per_item_sql);
    }
}
