<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Infrastructure\Enumerations\ProductStatusEnums;

class ProductRequest extends FormRequest
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
            'price' => 'required|int|digits_between:5,10',
            'inventory' => 'required|int|digits_between:1,5',
            'net_weight' => 'required|int|digits_between:1,7',
            'packaged_weight' => 'required|int|digits_between:2,8',
            'description' => 'required|string|max:1000',
            'photo_id' => 'nullable|exists:files,id',
            'status' => [
                'required',
                Rule::in([
                    ProductStatusEnums::AVAILABLE,
                    ProductStatusEnums::COMING_SOON,
                    ProductStatusEnums::NOT_PUBLISHED,
                    ProductStatusEnums::STOP_PRODUCTION,
                ]),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->where('parent_id', '!=', 0);
                })
            ]
        ];
    }
}
