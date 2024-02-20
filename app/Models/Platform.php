<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function addressables()
    {
        return $this->belongsToMany(Addressable::class, 'addressable_per_platform', 'platformid', 'addressableid')
            ->withPivot('url')->withTimestamps();
    }
}
