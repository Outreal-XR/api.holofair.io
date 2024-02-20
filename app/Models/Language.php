<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'native_name',
        'rtl',
    ];

    public function metaverses()
    {
        return $this->belongsToMany(Metaverse::class, 'languages_per_metaverse', 'language_id', 'metaverse_id')->withTimestamps();
    }
}
