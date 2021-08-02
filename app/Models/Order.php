<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Infrastructure\Enumerations\OrderStatusEnums;

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

    public static function getBasket($userId, $setBasket = true): self
    {
        $basket = self::query()
            ->where('user_id', $userId)
            ->where('status', OrderStatusEnums::BASKET)
            ->orderByDesc('id')
            ->first();

        if(!$basket && $setBasket === true) {
            $basket = self::setBasket($userId);
        }

        return $basket;
    }

    private static function setBasket($userId): self
    {
        DB::beginTransaction();

        try {
            DB::unprepared('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
            DB::insert(
                'INSERT INTO orders(user_id, amount, status, created_at)'
                .'SELECT ?, ?, ?, ? WHERE NOT EXISTS('
                .'SELECT 1 from orders'
                .'WHERE user_id = ?'
                .'AND status = ?'
                .'AND deleted_at IS NULL '
                .'LIMIT 1'
                .')',
                [
                    $userId,
                    0,
                    OrderStatusEnums::BASKET,
                    date('Y-m-d H:i:s'),
                    $userId,
                    OrderStatusEnums::BASKET,
                ]
            );

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollback();
        }

        return self::getBasket($userId, false);
    }

    public function orderPaysRelation()
    {
        return $this->hasMany(OrderPay::class, 'order_id');
    }
}
