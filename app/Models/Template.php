<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'metaverseid',
    ];

    public function metaverse()
    {
        return $this->belongsTo(Metaverse::class, 'metaverseid');
    }
}
