<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaverseSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'metaverse_id',
        'setting_id',
        'value',
    ];

    public function metaverse()
    {
        return $this->belongsTo(Metaverse::class);
    }

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }
}
