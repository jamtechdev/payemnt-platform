<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyReport extends Command
{
    protected $signature = 'app:send-daily-report {--period=daily : Report period: daily|weekly}';
    protected $description = 'Send periodic operational report to super admins';

    public function handle(): int
    {
        $period = (string) $this->option('period');
        if (! in_array($period, ['daily', 'weekly'], true)) {
            $this->error('Invalid period. Use daily or weekly.');

            return self::FAILURE;
        }

        if ($period === 'daily' && ! (bool) Setting::getValue('daily_report_enabled', false)) {
            $this->info('Daily report disabled.');

            return self::SUCCESS;
        }
        if ($period === 'weekly' && ! (bool) Setting::getValue('weekly_report_enabled', false)) {
            $this->info('Weekly report disabled.');

            return self::SUCCESS;
        }

        $startDate = $period === 'weekly' ? now()->subDays(7)->startOfDay() : now()->startOfDay();
        $endDate = now()->endOfDay();

        $todayNewCustomers = Customer::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $paymentsByProduct = Payment::query()
            ->selectRaw('users.product_id, SUM(payments.amount) as total_amount')
            ->join('users', 'users.id', '=', 'payments.customer_id')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->groupBy('users.product_id')
            ->get();
        $expiringSoon = Customer::query()
            ->selectRaw('partner_id, COUNT(*) as total')
            ->whereBetween('cover_end_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->groupBy('partner_id')
            ->get();

        $superAdmins = User::query()->role('super_admin')->get();
        $subject = ucfirst($period).' Admin Portal Summary';
        $body = "New Customers ({$period}): {$todayNewCustomers}\n\n".
            'Payments by Product: '.$paymentsByProduct->toJson()."\n\n".
            'Expiring in 7 Days by Partner: '.$expiringSoon->toJson();

        $recipients = Setting::getValue('daily_report_recipients', $superAdmins->pluck('email')->all());

        foreach ($recipients as $email) {
            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        }

        AuditLog::record('periodic_report_sent', null, [], [
            'period' => $period,
            'new_customers' => $todayNewCustomers,
            'super_admin_count' => $superAdmins->count(),
        ], auth()->user());

        $this->info(ucfirst($period).' report sent.');

        return self::SUCCESS;
    }
}
