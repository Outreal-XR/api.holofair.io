<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSettingPerMetaverse extends Model
{
    use HasFactory;

    protected $table = 'user_settings_per_metaverse';

    protected $fillable = [
        'user_id',
        'metaverse_id',
        'metaverse_setting_id',
        'value'
    ];

    public function metaverse()
    {
        return $this->belongsTo(Metaverse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function metaverseSetting()
    {
        return $this->belongsTo(MetaverseSetting::class);
    }
}
