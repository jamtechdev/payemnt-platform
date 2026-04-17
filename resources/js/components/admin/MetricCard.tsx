import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface MetricCardProps {
    label: string;
    value: string | number;
    valueClassName?: string;
    tone?: 'emerald' | 'blue' | 'amber' | 'violet';
}

const toneClasses: Record<NonNullable<MetricCardProps['tone']>, string> = {
    emerald: 'border-emerald-200/70 bg-emerald-50/50 dark:border-emerald-500/25 dark:bg-emerald-500/10',
    blue: 'border-blue-200/70 bg-blue-50/50 dark:border-blue-500/25 dark:bg-blue-500/10',
    amber: 'border-amber-200/70 bg-amber-50/50 dark:border-amber-500/25 dark:bg-amber-500/10',
    violet: 'border-violet-200/70 bg-violet-50/50 dark:border-violet-500/25 dark:bg-violet-500/10',
};

export default function MetricCard({ label, value, valueClassName, tone = 'emerald' }: MetricCardProps) {
    return (
        <Card className={`group overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md ${toneClasses[tone]}`}>
            <CardHeader className="pb-2">
                <CardTitle className="text-base text-muted-foreground">{label}</CardTitle>
            </CardHeader>
            <CardContent className={`text-3xl font-semibold text-foreground ${valueClassName ?? ''}`}>{value}</CardContent>
            <div className="h-1 w-full bg-gradient-to-r from-emerald-400/0 via-emerald-500/20 to-emerald-400/0 opacity-0 transition-opacity group-hover:opacity-100" />
        </Card>
    );
}
