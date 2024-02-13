<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'name',
        'display_name',
        'description',
        'default_value',
        'type'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_dashboard_settings', 'dashboard_setting_id', 'user_id')
            ->withPivot('value')
            ->withTimestamps();
    }
}
