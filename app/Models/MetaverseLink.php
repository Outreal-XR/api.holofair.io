<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaverseLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'metaverse_id',
    ];

    public function metaverse()
    {
        return $this->belongsTo(Metaverse::class);
    }
}
