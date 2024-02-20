<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addressable extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'description',
        'thumbnail',
        'type',
    ];

    public function metaverses()
    {
        return $this->belongsToMany(Metaverse::class, 'addressable_per_metaverse', 'addressableid', 'metaverseid')->withTimestamps();
    }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'addressable_per_platform', 'addressableid', 'platformid')
            ->withPivot('url')->withTimestamps();
    }
}
