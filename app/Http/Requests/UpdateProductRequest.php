<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Este SKU já está em uso.',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres.',
            'price.min' => 'O preço deve ser maior que zero.',
            'status.in' => 'O status deve ser active ou inactive.',
        ];
    }
}
