<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\GroupBuyProduct;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddToBasketRequest;
use Infrastructure\Enumerations\OrderStatusEnums;

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
     * @param AddToBasketRequest $request
     * @return array
     */
    public function addToBasket(AddToBasketRequest $request)
    {
        $validatedData = $request->validated();

        $quantity = $validatedData['quantity'];
        $product = Product::query()->findOrFail($validatedData['product_id']);
        $groupBuyProduct = GroupBuyProduct::query()->findOrFail($validatedData['group_buy_product_id']);

        $basket = Order::getBasket(Auth::id());
        $this->updateOrCreateOrderItem($product, $basket, $quantity, $groupBuyProduct);
        $this->calculateOrderAmount($basket);

        return [
            'order' => $basket,
            'orderItems' => $basket->orderItemsRelation()->get()
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

        $groupBuyProductId = null;
        $amount = $product->price * $quantity;
        $weight = $product->packaged_weight + ($product->net_weight * $quantity - 1);

        if ($groupBuyProduct != null) {
            $groupBuyProductId = $groupBuyProduct->id;
            $amount = $groupBuyProduct->price * $quantity;
        }

        if ($basketItem) {
            $quantity = $basketItem->quantity + $quantity;

            if ($quantity < 1) {
                $basketItem->delete();
            } else {
                $basketItem->amount = $amount;
                $basketItem->weight = $weight;
                $basketItem->quantity = $quantity;
                $basketItem->group_buy_product_id = $groupBuyProductId;

                $basketItem->saveOrFail();
            }
        } elseif ($quantity > 0) {
            $basket->orderItemsRelation()->create([
                'amount' => $amount,
                'weight' => $weight,
                'user_id' => Auth::id(),
                'quantity' => $quantity,
                'product_id' => $product->id,
                'group_buy_product_id' => $groupBuyProductId,
            ]);
        }
    }

    public function calculateOrderAmount(Order $order)
    {
        $amount = 0;

        foreach ($order->orderItemsRelation() as $item) {
            $amount += $item->amount;
        }

        $order->amount = $amount;

        $order->saveOrFail();
    }
}
