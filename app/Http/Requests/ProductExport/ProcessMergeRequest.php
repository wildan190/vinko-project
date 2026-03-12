<?php

namespace App\Http\Requests\ProductExport;

use Illuminate\Foundation\Http\FormRequest;

class ProcessMergeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'ids' => 'nullable|array',
            'ids.*' => 'exists:product_exports,id'
        ];
    }
}
