import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';

export type SettingsPageProps = {
    settings: {
        mail_host: string | null;
        mail_port: number | null;
        mail_from_address: string | null;
        mail_from_name: string | null;
        daily_report_enabled: boolean;
        daily_report_time: string;
        daily_report_recipients: string[];
        weekly_report_enabled: boolean;
    };
};

export default function Settings({ settings }: SettingsPageProps) {
    const { data, setData, transform, patch, processing, errors } = useForm({
        daily_report_enabled: settings.daily_report_enabled,
        daily_report_time: settings.daily_report_time,
        daily_report_recipients: settings.daily_report_recipients?.length
            ? settings.daily_report_recipients.join('\n')
            : '',
        weekly_report_enabled: settings.weekly_report_enabled,
    });

    transform((form) => ({
        ...form,
        daily_report_recipients: String(form.daily_report_recipients)
            .split(/[\s,;]+/)
            .map((s) => s.trim())
            .filter(Boolean),
    }));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(route('admin.settings.daily-report.update'), { preserveScroll: true });
    };

    return (
        <AdminLayout title="Settings">
            <div className="mx-auto max-w-3xl space-y-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Mail (read-only)</CardTitle>
                        <CardDescription>Configured via environment and config/mail.php.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm text-slate-600">
                        <p>
                            <span className="font-medium text-slate-800">Host:</span> {settings.mail_host ?? '—'}
                        </p>
                        <p>
                            <span className="font-medium text-slate-800">Port:</span> {settings.mail_port ?? '—'}
                        </p>
                        <p>
                            <span className="font-medium text-slate-800">From:</span> {settings.mail_from_name ?? ''} &lt;
                            {settings.mail_from_address ?? '—'}&gt;
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Scheduled reports</CardTitle>
                        <CardDescription>Daily and weekly summary emails to Super Admins.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="flex items-center gap-3">
                                <input
                                    id="daily_report_enabled"
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-slate-300"
                                    checked={data.daily_report_enabled}
                                    onChange={(e) => setData('daily_report_enabled', e.target.checked)}
                                />
                                <Label htmlFor="daily_report_enabled">Daily report enabled</Label>
                            </div>

                            <div className="flex items-center gap-3">
                                <input
                                    id="weekly_report_enabled"
                                    type="checkbox"
                                    className="h-4 w-4 rounded border-slate-300"
                                    checked={data.weekly_report_enabled}
                                    onChange={(e) => setData('weekly_report_enabled', e.target.checked)}
                                />
                                <Label htmlFor="weekly_report_enabled">Weekly report enabled</Label>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="daily_report_time">Send time (server timezone)</Label>
                                <Input
                                    id="daily_report_time"
                                    type="time"
                                    value={data.daily_report_time}
                                    onChange={(e) => setData('daily_report_time', e.target.value)}
                                />
                                {errors.daily_report_time && (
                                    <p className="text-sm text-destructive">{errors.daily_report_time}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="daily_report_recipients">Recipients (one email per line)</Label>
                                <textarea
                                    id="daily_report_recipients"
                                    className="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    value={data.daily_report_recipients}
                                    onChange={(e) => setData('daily_report_recipients', e.target.value)}
                                    disabled={!data.daily_report_enabled}
                                />
                                {errors.daily_report_recipients && (
                                    <p className="text-sm text-destructive">{errors.daily_report_recipients}</p>
                                )}
                            </div>

                            <Button type="submit" disabled={processing}>
                                Save report settings
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
