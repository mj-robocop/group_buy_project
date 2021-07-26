<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\Rule;
use App\Models\GroupBuyProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Infrastructure\Enumerations\ProductStatusEnums;
use Infrastructure\Enumerations\GroupBuyProductStatusEnums;

class AddToBasketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where(function ($query) {
                    $query->where('status', ProductStatusEnums::AVAILABLE)
                        ->whereNull('deleted_at');
                })
            ],
            'group_buy_product_id' => [
                'nullable',
                Rule::exists('group_buy_products', 'id')->where(function ($query) {
                    $query->where('product_id', $this->input('product_id'))
                        ->where('status', GroupBuyProductStatusEnums::ACTIVE)
                        ->where(function ($query) {
                            $query->where('start_time', '<=', now())
                                ->orWhereNull('start_time');
                        })
                        ->where(function ($query) {
                            $query->where('end_time', '<=', now())
                                ->orWhereNull('end_time');
                        })
                        ->whereNull('deleted_at');
                })
            ],
            'quantity' => [
                'required',
                'int',
                function ($attribute, $value, $fail) {
                    $productId = $this->input('product_id');
                    $product = Product::query()->find($productId);
                    $groupBuyProductId = $this->input('group_buy_product_id');
                    $groupBuyProduct = GroupBuyProduct::query()->find($groupBuyProductId);
                    $basket = Order::getBasket(Auth::id());
                    $item = $basket->orderItemsRelation()
                        ->where('product_id', $productId)
                        ->first();

                    if ($item != null) {
                        $value += $item->quantity;
                    }

                    if (
                        $product != null
                        && $product->inventory < $value
                    ) {
                        if ($product->inventory == 0) {
                            return $fail(__('messages.unavailable_product', [
                                'title' => $product->title
                            ]));
                        }

                        return $fail(__('messages.cart', [
                            'title' => $product->title,
                            'max' => $product->inventory
                        ]));
                    } elseif (
                        $groupBuyProduct != null
                        && (
                            $groupBuyProduct->quantity < $value
                            || (
                                $groupBuyProduct->user_quantity_limit != null
                                && $groupBuyProduct->user_quantity_limit < $value
                            )
                        )
                    ) {
                        if ($groupBuyProduct->quantity == 0) {
                            return $fail(__('messages.unavailable_group_buy_product', [
                                'title' => $groupBuyProduct->title
                            ]));
                        }

                        $maxQuantity = $groupBuyProduct->quantity;

                        if (
                            $groupBuyProduct->user_quantity_limit != null
                            && $maxQuantity > $groupBuyProduct->user_quantity_limit
                        ) {
                            $maxQuantity = $groupBuyProduct->user_quantity_limit;
                        }

                        return $fail(__('messages.group_buy_cart', [
                            'max' => $maxQuantity,
                            'title' => $groupBuyProduct->title
                        ]));
                    }
                },
            ]
        ];
    }
}
