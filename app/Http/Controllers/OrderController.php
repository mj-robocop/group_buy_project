<?php

namespace App\Http\Controllers;

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
        return Auth::user();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return Auth::user();
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
        return Auth::user();
    }

    /**
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getBasket()
    {
        return Auth::user();
    }

    /**
     * @param  Request  $request
     * @return Collection
     */
    public function addToBasket(Request $request)
    {
        return Auth::user();
    }

    /**
     * @param  Request  $request
     * @return Collection
     */
    public function editAddress(Request $request)
    {
        return Auth::user();
    }
}
