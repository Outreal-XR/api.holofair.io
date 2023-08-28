<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvitedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'is_accepted',
        'invited_by',
        'metaverse_id',
        'can_edit',
        'can_view',
    ];

    public function metaverse()
    {
        return $this->belongsTo(Metaverse::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
