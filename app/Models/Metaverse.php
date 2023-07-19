<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Metaverse extends Model
{
    use HasFactory;

    protected $fillable = [
        'userid',
        'uuid',
        'slug',
        'name',
        'description',
        'thumbnail',
        'url',
    ];

    public function addressables()
    {
        return $this->belongsToMany(Addressable::class, 'addressable_per_metaverse', 'metaverseid', 'addressableid');
    }

    public function templates()
    {
        return $this->hasMany(Template::class, 'metaverseid');
    }
}
