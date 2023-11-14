<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'display_name',
        'name',
        "default_value",
        "parent_id"
    ];

    public function metaverses()
    {
        return $this->belongsToMany(Metaverse::class, 'metaverse_settings', 'setting_id', 'metaverse_id')->withPivot('value');
    }

    public function children()
    {
        return $this->hasMany(Setting::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Setting::class, 'parent_id');
    }

    //get all children with pivot
    
}
