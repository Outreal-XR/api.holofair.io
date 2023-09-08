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

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    public function collaborators()
    {
        return $this->hasMany(Collaborator::class, 'metaverse_id');
    }

    public function generalSettings()
    {
        return $this->hasOne(GeneralSettings::class, 'metaverse_id');
    }

    public function avatarSettings()
    {
        return $this->hasOne(AvatarSettings::class, 'metaverse_id');
    }
}
