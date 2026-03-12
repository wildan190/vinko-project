<?php

namespace App\Http\Requests\ProductExport;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'image' => 'required|image|max:512000',
        ];
    }
}
