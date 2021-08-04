<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPay extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function orderRelation()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payBackRelation()
    {
        return $this->hasMany(OrderPayBack::class, 'pay_id');
    }
}
