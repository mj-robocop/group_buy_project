<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Infrastructure\Enumerations\GroupBuyProductStatusEnums;
use RuntimeException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\GroupBuyProduct;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddToBasketRequest;
use Infrastructure\Enumerations\OrderStatusEnums;
use Infrastructure\Enumerations\ProductStatusEnums;

class PaymentController extends Controller
{
    /**
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Throwable
     */
    public function doPayment($orderId)
    {
        $order = Order::query()->findOrFail($orderId);
        $orderItems = $order->orderItemsRelation()->get();

        $this->checkOrderItems($orderItems);
        $this->checkOrderAmount($orderItems, true);

        $order->status = OrderStatusEnums::UNPAID;

        $order->saveOrFail();

        return redirect(route('donePayment', ['orderId' => $order->id]));
    }

    /**
     * @param int $orderId
     * @return array
     */
    public function donePayment($orderId)
    {
        $order = Order::query()->find($orderId);
        $orderItems = [];

        if ($order != null) {
            $orderItems = $order->orderItemsRelation()->get();
        }

        return [
            'order' => $order,
            'items' => $orderItems
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function setPayBack(Request $request)
    {
        $order = Order::query()->find($id);
        $orderItems = [];

        if ($order != null) {
            $orderItems = $order->orderItemsRelation()->get();
        }

        return [
            'order' => $order,
            'items' => $orderItems
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getPayments(Request $request)
    {
        $order = Order::query()->find($id);
        $orderItems = [];

        if ($order != null) {
            $orderItems = $order->orderItemsRelation()->get();
        }

        return [
            'order' => $order,
            'items' => $orderItems
        ];
    }

    private function checkOrderItems($orderItems)
    {
        $orderItemIds = $orderItems->pluck('id');

        $product = Product::query()
            ->select('products.*')
            ->join('order_items', 'order_items.product_id', 'products.id')
            ->where(function ($query) {
                $query->where('products.status', '!=', ProductStatusEnums::AVAILABLE)
                    ->orWhereRaw('products.inventory' . ' < ' . 'order_items.quantity');
            })
            ->whereIn('order_items.id', $orderItemIds)
            ->first();

        if ($product != null) {
            throw new RuntimeException(__('messages.unavailable_product', ['title' => $product->title]));
        }

        $groupBuyProduct = GroupBuyProduct::query()
            ->select('group_buy_products.*')
            ->join('order_items', 'order_items.group_buy_product_id', 'group_buy_products.id')
            ->where(function ($query) {
                $query->where('group_buy_products.status', '!=', GroupBuyProductStatusEnums::ACTIVE)
                    ->orWhereRaw('group_buy_products.inventory' . ' < ' . 'order_items.quantity')
                    ->orWhereRaw('group_buy_products.user_quantity_limit' . ' < ' . 'order_items.quantity');
            })
            ->whereIn('order_items.id', $orderItemIds)
            ->first();

        if ($groupBuyProduct != null) {
            throw new RuntimeException(__('messages.unavailable_group_buy_product', ['title' => $groupBuyProduct->title]));
        }
    }

    private function checkOrderAmount($orderItems, $reCalculate = false)
    {
        $orderItemIds = $orderItems->pluck('id');

        $query = OrderItem::query()
            ->select(
                'order_items.id',
                'products.price as price',
                'products.title as title',
                'group_buy_products.price as group_buy_product_price',
                'group_buy_products.title as group_buy_product_title'
            )
            ->leftJoin('products', function ($join) {
                $join->on(
                    'products.id',
                    'order_items.product_id'
                )->whereRaw(
                    'order_items.amount' .
                    ' != ' .
                    'products.price'
                )->whereNull('order_items.group_buy_product_id');
            })
            ->leftJoin('group_buy_products', function ($join) {
                $join->on(
                    'group_buy_products.id',
                    'order_items.group_buy_product_id'
                )->whereRaw(
                    'order_items.amount' .
                    ' != ' .
                    'group_buy_products.price'
                )->whereNull('group_buy_products.deleted_at');
            })
            ->whereIn('order_items.id', $orderItemIds)
            ->first();

        if (
            $query->title == null
            || $query->group_buy_product_title == null
        ) {
            return;
        }

        if (!$reCalculate) {
            if ($query->title != null) {
                throw new RuntimeException(__('messages.unavailable_product', [
                    'title' => $query->title
                ]));
            } else {
                throw new RuntimeException(__('messages.unavailable_group_buy_product', [
                    'title' => $query->group_buy_product_title
                ]));
            }
        }

        if ($query->price != null) {
            $amount = $query->price;
        } else {
            $amount = $query->group_buy_product_price;
        }

        foreach ($orderItems as $orderItem) {
            if ($orderItem->id == $query->id) {
                $orderItem->amount = $amount;

                $orderItem->saveOrFail();
            }
        }

        throw new RuntimeException(__('messages.price_is_changed'));
    }
}
