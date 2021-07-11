<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\GroupBuyProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\GroupBuyProductRequest;

class GroupBuyProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin', ['except' => ['index', 'show']]);
    }

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

        return $groupBuyProducts = GroupBuyProduct::query()
            ->where(function ($query) {
                $query->whereNull('start_time')
                ->orWhere('start_time', '<=', Carbon::now());
            })
            ->where(function ($query) {
                $query->whereNull('end_time')
                ->orWhere('end_time', '>', Carbon::now());
            })
            ->limit($validatedData['limit'] ?? 10)
            ->offset($validatedData['offset'] ?? 0)
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  GroupBuyProductRequest $request
     * @return Response
     */
    public function store(GroupBuyProductRequest $request)
    {
        $inputs = $request->validated();
        $inputs += [
            'created_by' => Auth::id(),
        ];

        return GroupBuyProduct::create($inputs);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return GroupBuyProduct::find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param GroupBuyProductRequest $request
     * @param int $id
     *
     * * @throws \Throwable
     *
     * @return Collection|\Illuminate\Database\Eloquent\Model
     */
    public function update(GroupBuyProductRequest $request, $id)
    {
        $inputs = $request->validated();
        $groupBuyProduct = GroupBuyProduct::query()->findOrFail($id);

        foreach ($inputs as $key => $value) {
            $groupBuyProduct->$key = $value;
        }

        $groupBuyProduct->saveOrFail();

        return $groupBuyProduct;
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
        $groupBuyProduct = GroupBuyProduct::query()->findOrFail($id);

        $groupBuyProduct->delete();

        return __('messages.group_buy_product_id_deleted', ['title' => $groupBuyProduct->title]);
    }
}
