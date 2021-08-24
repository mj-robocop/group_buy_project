<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ProductRequest;
use Illuminate\Database\Eloquent\Collection;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin', ['except' => ['index', 'show']]);
    }

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

        $products = Product::query()
            ->with('reviews')
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();

        $result = [];

        foreach ($products as $product) {
            $reviews = $product->reviews;
            $product->unsetRelation('reviews');

            $result [] = [
                'product' => $product,
                'reviews' => $reviews
            ];
        }

        return $result;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ProductRequest $request
     * @return Response
     */
    public function store(ProductRequest $request)
    {
        $inputs = $request->validated();
        $inputs += [
            'created_by' => Auth::id(),
        ];

        return Product::create($inputs);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return array|null[]
     */
    public function show($id)
    {
        $product = Product::find($id);
        $reviews = [];

        if ($product != null) {
            $reviews = $product->reviews()->get();
        }

        return [
            'product' => $product,
            'reviews' => $reviews
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\ProductRequest $request
     * @param int $id
     *
     * * @throws \Throwable
     *
     * @return Collection|\Illuminate\Database\Eloquent\Model
     */
    public function update(ProductRequest $request, $id)
    {
        $inputs = $request->validated();
        $product = Product::query()->findOrFail($id);

        foreach ($inputs as $key => $value) {
            $product->$key = $value;
        }

        $product->saveOrFail();

        return $product;
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
        $product = Product::query()->findOrFail($id);

        $product->delete();

        return __('messages.product_is_deleted', ['title' => $product->title]);
    }
}
