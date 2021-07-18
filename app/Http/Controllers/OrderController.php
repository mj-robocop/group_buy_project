<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @param  Request  $request
     * @return Collection
     */
    public function editAddress(Request $request)
    {
        dd(5147685);

        return Auth::user();
    }
}
