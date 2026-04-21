<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'daily_report_recipients' => [
                Rule::requiredIf($this->boolean('daily_report_enabled')),
                'array',
                'min:1',
            ],
            'daily_report_recipients.*' => ['email'],
            'weekly_report_enabled' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $recipients = $this->input('daily_report_recipients');
        if (is_string($recipients)) {
            $emails = preg_split('/[\s,;]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $this->merge(['daily_report_recipients' => array_values(array_unique($emails))]);
        }
    }
}
