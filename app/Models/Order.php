<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function userRelation()
    {
        return $this->hasOne(User::class);
    }

    public function orderItemsRelation()
    {
        return $this->hasMany(OrderItem::class);
    }
}
