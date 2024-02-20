<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaverseSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'default_value'
    ];

    public function userSettings()
    {
        return $this->hasMany(UserSettingPerMetaverse::class, 'metaverse_setting_id');
    }
}
