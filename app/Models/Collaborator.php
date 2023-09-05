<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'status',
        'role',
        'token',
        'token_expiry',
        'invited_by',
        'metaverse_id',
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
