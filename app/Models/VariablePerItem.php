<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariablePerItem extends Model
{
    use HasFactory;

    protected $table = 'variables_per_item';

    public $timestamps = false;

    protected $fillable = [
        'room',
        'guid',
        'key',
        'value',
    ];
}
