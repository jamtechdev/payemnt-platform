<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExportReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => ['required', 'in:csv,excel,pdf'],
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'partner_id' => ['nullable', 'integer', 'exists:users,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
