<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Infrastructure\Enumerations\OrderStatusEnums;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function ordersRelation()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function ordersPaidRelation()
    {
        return $this->ordersRelation()->where(
            'status',
            OrderStatusEnums::PAID
        );
    }

    public function orderBasketRelation()
    {
        return $this->ordersRelation()->where(
            'status',
            OrderStatusEnums::BASKET
        );
    }

    public function orderItemsRelation()
    {
        return $this->hasMany(OrderItem::class, 'userId')
            ->join('orders', function (JoinClause $clause) {
                $clause->on('order_items.order_id', 'orders.id');
                $clause->whereNull('orders.deleted_at');
                $clause->whereNull('order_items.deleted_at');
            });
    }

    public function orderPaysRelation()
    {
        return $this->hasMany(OrderPay::class, 'user_id');
    }
}
