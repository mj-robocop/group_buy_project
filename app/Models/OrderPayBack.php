<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPayBack extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function orderPaysRelation()
    {
        return $this->belongsTo(OrderPay::class, 'pay_id');
    }

    public function orderItemsRelation()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
