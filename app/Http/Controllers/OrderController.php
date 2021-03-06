<?php

namespace App\Http\Controllers;

use RuntimeException;
use App\Models\Order;
use App\Models\Review;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\GroupBuyProduct;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddToBasketRequest;
use Infrastructure\Enumerations\OrderStatusEnums;
use Infrastructure\Enumerations\OrderItemStatusEnums;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'limit' => 'nullable|int|max:50',
            'offset' => 'nullable|int',
        ]);

        $orders = Auth::user()->ordersRelation()
            ->with('orderItemsRelation')
            ->where('orders.status', '!=', OrderStatusEnums::BASKET)
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();

        $result = [];

        foreach ($orders as $order) {
            $orderItems = $order->orderItemsRelation;
            $order->unsetRelation('orderItemsRelation');

            $result [] = [
                'order' => $order,
                'items' => $orderItems
            ];
        }

        return $result;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return array
     */
    public function show($id)
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
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return string
     */
    public function destroy($id)
    {
        $order = Order::query()->findOrFail($id);

        return Auth::user();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getBasket(Request $request)
    {
        $basket = Order::getBasket(Auth::id(), false);
        $items = [];

        if ($basket != null) {
            $items = $basket->orderItemsRelation()->get();
        }

        return [
            'order' => $basket,
            'items' => $items
        ];
    }

    /**
     * @param AddToBasketRequest $request
     * @return array
     */
    public function addToBasket(AddToBasketRequest $request)
    {
        $validatedData = $request->validated();

        $quantity = $validatedData['quantity'];
        $product = Product::query()->findOrFail($validatedData['product_id']);
        $groupBuyProduct = null;

        if (array_key_exists('group_buy_product_id', $validatedData)) {
            $groupBuyProduct = GroupBuyProduct::query()->findOrFail($validatedData['group_buy_product_id']);
        }

        $basket = Order::getBasket(Auth::id());
        $this->updateOrCreateOrderItem($product, $basket, $quantity, $groupBuyProduct);
        $this->calculateOrderAmount($basket);

        return [
            'order' => $basket,
            'items' => $basket->orderItemsRelation()->get()
        ];
    }

    /**
     * @param Request $request
     * @return Order
     * @throws \Throwable
     */
    public function editAddress(Request $request)
    {
        $validatedData = $request->validate([
            'address' => 'required|string|min:10|max:255',
            'receiver_name' => 'required|string|min:2|max:50',
            'receiver_mobile' => 'required|string|min:7|max:20',
            'postal_code' => 'nullable|int|digits:10',
            'province' => [
                'required',
                Rule::exists('enumerations', 'id')->where(function ($query) {
                    $query->where('parent_id', 2);
                })
            ],
            'city' => [
                'required',
                Rule::exists('enumerations', 'id')->where(function ($query) use ($request) {
                    $query->where('parent_id', $request->input('province'));
                })
            ]
        ]);

        /** @var Order $basket */
        $basket = Auth::user()->orderBasketRelation()->first();

        if ($basket == null) {
            abort(422, __('messages.no_basket_found'));
        }

        foreach ($validatedData as $key => $value) {
            $basket->$key = $value;
        }

        $basket->saveOrFail();

        return $basket;
    }

    private function updateOrCreateOrderItem($product, Order $basket, $quantity, $groupBuyProduct = null)
    {
        $basketItem = $basket->orderItemsRelation()
            ->where('product_id', $product->id)
            ->first();

        if ($basketItem) {
            $quantity = $basketItem->quantity + $quantity;

            if ($quantity < 1) {
                $basketItem->delete();
            } else {
                $data = $this->getAddToBasketData($product, $quantity, $groupBuyProduct);

                $basketItem->amount = $data['amount'];
                $basketItem->weight = $data['weight'];
                $basketItem->quantity = $data['quantity'];
                $basketItem->group_buy_product_id = $data['groupBuyProductId'];

                $basketItem->saveOrFail();
            }
        } elseif ($quantity > 0) {
            $data = $this->getAddToBasketData($product, $quantity, $groupBuyProduct);

            $basket->orderItemsRelation()->create([
                'amount' => $data['amount'],
                'weight' => $data['weight'],
                'user_id' => Auth::id(),
                'quantity' => $data['quantity'],
                'product_id' => $product->id,
                'group_buy_product_id' => $data['groupBuyProductId'],
            ]);
        }
    }

    public function calculateOrderAmount(Order $order)
    {
        $amount = 0;

        foreach ($order->orderItemsRelation()->get() as $item) {
            $amount += ($item->amount * $item->quantity) + $item->delivery_cost;
        }

        $order->amount = $amount;

        $order->saveOrFail();
    }

    private function getAddToBasketData($product, $quantity, $groupBuyProduct)
    {
        $groupBuyProductId = null;
        $amount = $product->price;
        $weight = $product->packaged_weight + ($product->net_weight * ($quantity - 1));

        if ($groupBuyProduct != null) {
            $amount = $groupBuyProduct->price;
            $groupBuyProductId = $groupBuyProduct->id;
        }

        return [
            'amount' => $amount,
            'weight' => $weight,
            'quantity' => $quantity,
            'groupBuyProductId' => $groupBuyProductId
        ];
    }

    public function cancelOrderItem($id)
    {
        $orderItem = OrderItem::query()->find($id);

        if (
            $orderItem == null
            || $orderItem->status == null
            || $orderItem->user_id != Auth::id()
        ) {
            throw new RuntimeException(__('messages.can_not_set_pay_back'));
        }

        DB::beginTransaction();
        try {
            if (in_array($orderItem->status, [
                OrderItemStatusEnums::VERIFIED,
                OrderItemStatusEnums::PREPARATION,
                OrderItemStatusEnums::WAITING_FOR_GROUP_BUY,
            ])) {
                $orderItem->status = OrderItemStatusEnums::CANCELED_BEFORE_POSTING;
                $orderItem->saveOrFail();

                $paymentController = new PaymentController();
                $payBack = $paymentController->setPayBack($id);
            } elseif ($orderItem->status == OrderItemStatusEnums::POSTED) {
                $orderItem->status = OrderItemStatusEnums::RETURN_REQUEST;
                $orderItem->saveOrFail();
            } else {
                return ['result' => false];
            }
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return ['result' => false];
        }

        return ['result' => true];
    }

    public function changeOrderItemsStatus(Request $request, $ids)
    {
        $validatedData = $request->validate([
            'postTrackingCode' => 'nullable|string|max:64',
            'status' => [
                'required',
                Rule::in(OrderItemStatusEnums::ALL),
            ],
        ]);

        OrderItem::query()
            ->whereIn('id', explode(',', $ids))
            ->update([
                'status' => $validatedData['status']
            ]);

        if (array_key_exists('postTrackingCode', $validatedData)) {
            OrderItem::query()
                ->whereIn('id', explode(',', $ids))
                ->update([
                    'post_tracking_code' => $validatedData['postTrackingCode']
                ]);
        }

        return ['result' => true];
    }

    public function createReview(Request $request, $id)
    {
        $validatedData = $request->validate([
            'star' => 'required|int|min:1|max:5',
            'description' => 'nullable|string|max:256'
        ]);

        $orderItem = OrderItem::query()->findOrFail($id);

        if (
            $orderItem->status == null
            || $orderItem->status == OrderItemStatusEnums::WAITING_FOR_GROUP_BUY
        ) {
            return ['result' => false];
        }

        $reviewExists = Review::withTrashed()
            ->where('user_id', Auth::id())
            ->where('order_id', $orderItem->order_id)
            ->where('product_id', $orderItem->product_id)
            ->exists();

        if ($reviewExists) {
            throw new RuntimeException(__('messages.has_reviewed_this_item_before'));
        }

        $review = new Review();

        $review->user_id = Auth::id();
        $review->star = $validatedData['star'];
        $review->order_id = $orderItem->order_id;
        $review->product_id = $orderItem->product_id;
        $review->description = $validatedData['description'] ?? null;

        return ['result' => $review->saveOrFail()];
    }
}
