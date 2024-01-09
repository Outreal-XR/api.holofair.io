<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'interval', // 'month', 'year        
        'stripe_plan_id',
        'lookup_key',
    ];

    protected $appends = ['isCurrentPlan'];

    public function getIsCurrentPlanAttribute()
    {
        // $user = auth()->user();

        // if (!$user) {
        //     return false;
        // }

        // $userCurrentPlan = $user->subscription;

        // if (!$userCurrentPlan) {
        //     return false;
        // }

        // return $userCurrentPlan->plan_id === $this->id;

        return false;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id', 'id');
    }
}
