import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface MetricCardProps {
    label: string;
    value: string | number;
    valueClassName?: string;
}

export default function MetricCard({ label, value, valueClassName }: MetricCardProps) {
    return (
        <Card className="group overflow-hidden transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
            <CardHeader className="pb-2">
                <CardTitle className="text-base text-slate-600">{label}</CardTitle>
            </CardHeader>
            <CardContent className={`text-3xl font-semibold text-slate-900 ${valueClassName ?? ''}`}>{value}</CardContent>
            <div className="h-1 w-full bg-gradient-to-r from-emerald-400/0 via-emerald-500/20 to-emerald-400/0 opacity-0 transition-opacity group-hover:opacity-100" />
        </Card>
    );
}
