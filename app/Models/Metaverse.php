<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Metaverse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'userid',
        'uuid',
        'slug',
        'name',
        'description',
        'thumbnail',
        'url',
    ];

    protected $appends = [
        'is_collaborator',
        'is_blocked',
        'is_owner',
        'links',
    ];

    //relations
    public function addressables()
    {
        return $this->belongsToMany(Addressable::class, 'addressable_per_metaverse', 'metaverseid', 'addressableid')->withTimestamps();
    }

    public function template()
    {
        return $this->hasOne(Template::class, 'metaverseid');
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
        return $this->belongsToMany(MetaverseSetting::class, 'settings_per_metaverse', 'metaverse_id', 'metaverse_setting_id')->withPivot('value')->withPivot('id')->withTimestamps();
    }

    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'metaverse_id', 'blocked_user_id')->withTimestamps();
    }

    public function links()
    {
        return $this->hasMany(MetaverseLink::class, 'metaverse_id');
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'languages_per_metaverse', 'metaverse_id', 'language_id')->withTimestamps();
    }

    //scopes
    public function viewers()
    {
        return $this->invitedUsers()->where('role', 'viewer')->whereIn('status', ['accepted', 'blocked']);
    }

    public function collaborators()
    {
        return $this->invitedUsers()->where('role', 'editor')->whereIn('status', ['accepted', 'blocked']);
    }

    //attributes
    public function getIsCollaboratorAttribute()
    {
        return $this->isOwner() || $this->isCollaborator();
    }

    public function getIsBlockedAttribute()
    {
        return $this->isBlocked();
    }

    public function getIsOwnerAttribute()
    {
        return $this->isOwner();
    }

    public function getLinksAttribute()
    {
        return $this->links()->get();
    }

    //methods|checkers
    public function canAccessMetaverse()
    {
        return ($this->isOwner() || $this->isCollaborator() || $this->isViewer()) && !$this->isBlocked();
    }

    public function canUpdateMetaverse()
    {
        return $this->isOwner() || $this->isCollaborator() && !$this->isBlocked();
    }

    public function canDeleteMetaverse()
    {
        return $this->isOwner();
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

        return $this->collaborators->where('email', $user->email)->isNotEmpty();
    }

    public function isViewer()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->viewers->where('email', $user->email)->isNotEmpty();
    }

    public function isBlocked()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->blockedUsers->where('id', $user->id)->isNotEmpty();
    }
}
