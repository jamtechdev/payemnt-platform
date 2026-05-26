import AdminLayout from '@/layouts/AdminLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Mail } from 'lucide-react';

export type SettingsPageProps = {
    settings: {
        mail_host: string | null;
        mail_port: number | null;
        mail_from_address: string | null;
        mail_from_name: string | null;
    };
};

export default function Settings({ settings }: SettingsPageProps) {
    return (
        <AdminLayout title="Settings">
            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Mail className="h-4 w-4 text-muted-foreground" />
                            Email configuration
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm">
                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
                            <span className="text-muted-foreground">Host</span>
                            <span className="font-mono text-xs font-medium">{settings.mail_host ?? '—'}</span>
                        </div>
                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
                            <span className="text-muted-foreground">Port</span>
                            <span className="font-mono text-xs font-medium">{settings.mail_port ?? '—'}</span>
                        </div>
                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
                            <span className="text-muted-foreground">From address</span>
                            <span className="font-mono text-xs font-medium">{settings.mail_from_address ?? '—'}</span>
                        </div>
                        <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
                            <span className="text-muted-foreground">From name</span>
                            <span className="font-mono text-xs font-medium">{settings.mail_from_name ?? '—'}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
