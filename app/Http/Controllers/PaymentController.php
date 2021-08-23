<?php

namespace App\Http\Controllers;

use RuntimeException;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderPay;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\OrderPayBack;
use Illuminate\Validation\Rule;
use App\Models\GroupBuyProduct;
use Illuminate\Support\Facades\DB;
use Infrastructure\Enumerations\OrderStatusEnums;
use Infrastructure\Enumerations\ProductStatusEnums;
use Infrastructure\Enumerations\OrderItemStatusEnums;
use Infrastructure\Enumerations\GroupBuyProductStatusEnums;

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

        if ($order->address == null) {
            throw new RuntimeException(__('messages.address_is_not_set'));
        }

        $this->checkOrderItems($orderItems);
        $this->checkOrderItemsAmount($order, $orderItems, true);
        $this->checkOrderAmount($order, $orderItems, true);

        $order->status = OrderStatusEnums::UNPAID;

        $order->saveOrFail();

        $pay = $order->orderPaysRelation()->create([
            'amount' => $order->amount,
            'status' => $order->status,
            'user_id' => $order->user_id,
        ]);

        return redirect(route('donePayment', [
            'payId' => $pay->id,
            'orderId' => $order->id
        ]));
    }

    /**
     * @param int $orderId
     * @param int $payId
     * @return array
     * @throws \Throwable
     */
    public function donePayment($orderId, $payId)
    {
        $pay = OrderPay::query()->find($payId);
        $order = Order::query()->find($orderId);

        if (
            $pay == null
            || $order == null
            || $pay->status != OrderStatusEnums::UNPAID
            || $order->status != OrderStatusEnums::UNPAID
        ) {
            throw new RuntimeException(__('messages.order_is_invalid'));
        }

        $orderItems = $order->orderItemsRelation()->get();

        $this->checkOrderItems($orderItems);
        $this->checkOrderItemsAmount($order, $orderItems);
        $this->checkOrderAmount($order, $orderItems);

        DB::beginTransaction();
        try {
            $pay->status = OrderStatusEnums::PAID;
            $order->status = OrderStatusEnums::PAID;

            $pay->saveOrFail();
            $order->saveOrFail();

            foreach ($orderItems as $item) {
                $product = Product::query()->findOrFail($item->product_id);

                if ($item->group_buy_product_id != null) {
                    $groupBuyProduct = GroupBuyProduct::query()->findOrFail($item->group_buy_product_id);
                    $product->inventory -= $item->quantity;
                    $groupBuyProduct->inventory -= $item->quantity;
                    $item->status = OrderItemStatusEnums::WAITING_FOR_GROUP_BUY;

                    $groupBuyProduct->saveOrFail();
                } else {
                    $product->inventory -= $item->quantity;
                    $item->status = OrderItemStatusEnums::VERIFIED;
                }

                $item->saveOrFail();
                $product->saveOrFail();
            }
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw new RuntimeException(__('messages.order_is_invalid') . $throwable->getMessage());
        }

        return [
            'order' => $order,
            'items' => $orderItems
        ];
    }

    /**
     * @param int $orderItemId
     * @return array
     */
    public function setPayBack($orderItemId)
    {
        $orderItem = OrderItem::query()->findOrFail($orderItemId);

        if ($orderItem->status == null) {
            throw new RuntimeException(__('messages.can_not_set_pay_back'));
        }

        $pay = OrderPay::query()
            ->where('order_id', $orderItem->order_id)
            ->where('status', OrderStatusEnums::PAID)
            ->first();

        if ($pay == null) {
            throw new RuntimeException(__('messages.can_not_set_pay_back'));
        }

        return $pay->payBackRelation()->firstOrCreate([
            'user_id' => $orderItem->user_id,
            'order_item_id' => $orderItem->id
            ],
            [
            'status' => OrderStatusEnums::UNPAID,
            'amount' => ($orderItem->amount * $orderItem->quantity) + $orderItem->delivery_cost,
        ]);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getPayments(Request $request)
    {

        $validatedData = $request->validate([
            'limit' => 'nullable|int|max:50',
            'offset' => 'nullable|int',
        ]);

        $pays = OrderPay::query()
            ->with('orderRelation')
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();

        return [
            'pays' => $pays
        ];
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getPaybacks(Request $request)
    {

        $validatedData = $request->validate([
            'limit' => 'nullable|int|max:50',
            'offset' => 'nullable|int',
        ]);

        $payBacks = OrderPayBack::query()
            ->with('orderItemsRelation')
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();

        return [
            'payBacks' => $payBacks
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

    private function checkOrderItemsAmount($order, $orderItems, $reCalculate = false)
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

        $orderController = new OrderController();
        $orderController->calculateOrderAmount($order);

        throw new RuntimeException(__('messages.price_is_changed'));
    }

    private function checkOrderAmount($order, $orderItems, $reCalculate = false)
    {
        $amount = 0;

        foreach ($orderItems as $item) {
            $amount += $item->amount * $item->quantity;
        }

        if ($order->amount == $amount) {
            return true;
        }

        if (!$reCalculate) {
            throw new RuntimeException(__('messages.order_amount_is_wrong'));
        }

        $orderController = new OrderController();
        $orderController->calculateOrderAmount($order);

        throw new RuntimeException(__('messages.price_is_changed'));
    }

    public function changePayBackStatus(Request $request, $ids)
    {
        $validatedData = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    OrderStatusEnums::PAID,
                    OrderStatusEnums::UNPAID,
                ])
            ],
        ]);

        OrderPayBack::query()
            ->whereIn('id', explode(',', $ids))
            ->update([
                'status' => $validatedData['status']
            ]);

        return ['result' => true];
    }
}
