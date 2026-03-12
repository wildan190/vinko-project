<?php

namespace App\Http\Requests\ProductExport;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'file' => 'required|mimes:xlsx,xls,csv'
        ];
    }
}
