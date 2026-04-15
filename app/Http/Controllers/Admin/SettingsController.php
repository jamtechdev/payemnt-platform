<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateDailyReportSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/Settings', [
            'settings' => [
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
                'daily_report_enabled' => (bool) Setting::getValue('daily_report_enabled', false),
                'daily_report_time' => (string) Setting::getValue('daily_report_time', '08:00'),
                'daily_report_recipients' => (array) Setting::getValue('daily_report_recipients', []),
                'weekly_report_enabled' => (bool) Setting::getValue('weekly_report_enabled', false),
            ],
        ]);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        return back()->with('success', 'Email settings updated.');
    }

    public function updateDailyReport(UpdateDailyReportSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Setting::setValue('daily_report_enabled', (bool) $data['daily_report_enabled']);
        Setting::setValue('daily_report_time', $data['daily_report_time']);
        Setting::setValue('daily_report_recipients', $data['daily_report_recipients']);
        Setting::setValue('weekly_report_enabled', (bool) ($data['weekly_report_enabled'] ?? false));

        return back()->with('success', 'Daily report settings updated.');
    }
}
