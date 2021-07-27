<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Infrastructure\Enumerations\GroupBuyProductStatusEnums;

class GroupBuyProductRequest extends FormRequest
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
            'title' => 'required|string|min:3|max:100',
            'product_id' => 'required|exists:products,id',
            'price' => 'required|int|digits_between:5,10',
            'inventory' => 'required|int|min:1|max:10000',
            'description' => 'required|string|max:1000',
            'user_quantity_limit' => 'nullable|int|min:1',
            'is_special' => 'nullable|boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'status' => [
                'required',
                Rule::in([
                    GroupBuyProductStatusEnums::ACTIVE,
                    GroupBuyProductStatusEnums::DISABLE,
                    GroupBuyProductStatusEnums::COMING_SOON,
                ]),
            ]
        ];
    }
}
