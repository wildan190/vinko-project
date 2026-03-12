<?php

namespace App\Http\Requests\ProductExport;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'ids' => 'required|array',
            'ids.*' => 'exists:product_exports,id'
        ];
    }
}
