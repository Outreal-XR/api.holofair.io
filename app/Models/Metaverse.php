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
        return $this->isOwner() || $this->isCollaborator();
    }

    public function isOwner()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->userid === $user->id;
    }

    public function isCollaborator()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->invitedUsers->where('email', $user->email)
            ->where('status', 'accepted')
            ->where('role', 'editor')
            ->isNotEmpty();
    }

    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'metaverse_id', 'blocked_user_id');
    }

    public function isBlocked()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->blockedUsers->where('id', $user->id)->isNotEmpty();
    }

    //get blocked users from invited users
    public function blockedCollaborators()
    {
        return $this->invitedUsers->where('status', 'blocked');
    }
}
