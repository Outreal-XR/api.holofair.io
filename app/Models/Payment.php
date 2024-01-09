<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'subscription_id',
        'st_customer_id',
        'st_subscription_id',
        'st_payment_intent_id',
        'st_payment_method',
        'st_payment_status',
        'date',
        'end_at',
    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'id', 'subscription_id');
    }
}
