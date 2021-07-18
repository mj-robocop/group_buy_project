<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

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
        return Auth::user()->ordersRelation()->paginate();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return Order::query()->find($id);
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
        return Auth::user()->orderBasketRelation()->first();
    }

    /**
     * @param  Request  $request
     * @return Collection
     */
    public function addToBasket(Request $request)
    {
        dd(4456);

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
