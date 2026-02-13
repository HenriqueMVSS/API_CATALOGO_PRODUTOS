<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'O SKU é obrigatório.',
            'sku.unique' => 'Este SKU já está em uso.',
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres.',
            'price.required' => 'O preço é obrigatório.',
            'price.min' => 'O preço deve ser maior que zero.',
            'status.in' => 'O status deve ser active ou inactive.',
        ];
    }
}
