<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Infrastructure\Enumerations\OrderStatusEnums;
use Infrastructure\Enumerations\ProductStatusEnums;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return Collection
     */
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'limit' => 'nullable|int|max:50',
            'offset' => 'nullable|int',
        ]);

        return Auth::user()->ordersRelation()
            ->with('orderItemsRelation')
            ->where('orders.status', '!=', OrderStatusEnums::BASKET)
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Order
     */
    public function show($id)
    {
        return Order::query()->with('orderItemsRelation')->find($id);
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
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getBasket(Request $request)
    {
        return Auth::user()->orderBasketRelation()
            ->with('orderItemsRelation')
            ->first();
    }

    /**
     * @param  Request  $request
     * @return Collection
     */
    public function addToBasket(Request $request)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|numeric',
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(function ($query) use ($request) {
                    $query->where('status', ProductStatusEnums::AVAILABLE)
                        ->where('inventory', '>=', $request->input('quantity'));
                })
            ],
            'group_buy_product_id' => [
                'required',
                Rule::exists('group_buy_products', 'id')->where(function ($query) use ($request) {
                    $query->where('end_time', '>', Carbon::now())
                        ->where('start_time', '<=', Carbon::now())
                        ->where('product_id', $request->input('product_id'))
                        ->where('inventory', '>=', $request->input('quantity'))
                        ->where('user_quantity_limit', '>=', $request->input('quantity'));
                })
            ]
        ]);

        $quantity = $validatedData['quantity'];
        $product = Product::query()->findOrFail($validatedData['product_id']);

        $basket = Order::getBasket(Auth::id());

        $basket->orderItemsRelation()
            ->where('product_id', $product->id);

        $basket->orderItemsRelation()->create([
            'user_id' => Auth::id(),
            'quantity' => $quantity,
            'amount' => $product->price,
            'weight' => $product->weight * $quantity,
            'product_id' => $validatedData['product_id'],
        ]);

        return Auth::user();
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
}
