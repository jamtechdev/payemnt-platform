<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                     => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'image'                    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status'                   => ['required', 'in:active,inactive'],
            'cover_duration_options'   => ['required', 'array', 'min:1'],
            'cover_duration_options.*' => ['integer', 'min:1'],
            'fields'                   => ['array'],
            'fields.*.name'            => ['required', 'string', 'max:100'],
            'fields.*.label'           => ['required', 'string', 'max:255'],
            'fields.*.type'            => ['required', 'in:text,textarea,number,date,datetime,dropdown,boolean,email,phone'],
            'fields.*.is_required'     => ['boolean'],
            'fields.*.options'         => ['nullable', 'array'],
        ];
    }
}
