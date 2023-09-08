<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvatarSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'ready_player_me',
        'holofair_avatar',
        'holofair_avatar_id',
        'custom_avatar',
        'custom_avatar_url',
    ];
}
