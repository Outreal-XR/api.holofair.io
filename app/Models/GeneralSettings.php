<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_landing_page',
        'allow_public_chat',
        'allow_direct_messages',
        'allow_direct_calls',
        'custom_spawn_point',
        'spawn_point',
    ];
}
