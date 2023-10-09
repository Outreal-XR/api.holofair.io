<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    protected $appends = ['is_collaborator'];

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

    public function invitedUsers()
    {
        return $this->hasMany(InvitedUser::class, 'metaverse_id');
    }

    public function settings()
    {
        return $this->belongsToMany(Setting::class, 'metaverse_settings', 'metaverse_id', 'setting_id')->withPivot('value');
    }

    public function getIsCollaboratorAttribute()
    {
        return $this->canUpdateMetaverse();
    }

    public function canUpdateMetaverse()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $is_owner = $this->userid === $user->id;

        if ($is_owner) {
            return true;
        }

        return $this->invitedUsers->where('email', $user->email)
            ->where('status', 'accepted')
            ->where('role', 'editor')
            ->isNotEmpty();
    }
}
