<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyReportSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'daily_report_enabled' => ['required', 'boolean'],
            'daily_report_time' => ['required', 'date_format:H:i'],
            'daily_report_recipients' => ['required', 'array', 'min:1'],
            'daily_report_recipients.*' => ['email'],
            'weekly_report_enabled' => ['nullable', 'boolean'],
        ];
    }
}
