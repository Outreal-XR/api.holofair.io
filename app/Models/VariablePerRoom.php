<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariablePerRoom extends Model
{
    use HasFactory;

    protected $table = 'variables_per_room';

    public $timestamps = false;

    protected $fillable = [
        'room',
        'guid',
        'key',
        'value',
    ];
}
