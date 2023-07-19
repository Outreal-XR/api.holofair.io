<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPerRoom extends Model
{
    use HasFactory;

    protected $table = 'items_per_room';

    public $timestamps = false;

    protected $fillable = [
        'room',
        'guid',
        'position',
        'rotation',
        'scale',
    ];
}
