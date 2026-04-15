import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ReactNode } from 'react';

interface EntityListCardProps {
    title: string;
    emptyText: string;
    items: Array<{ key: string; content: ReactNode }>;
}

export default function EntityListCard({ title, emptyText, items }: EntityListCardProps) {
    return (
        <Card className="transition-all duration-300 hover:shadow-md">
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {items.length === 0 && <p className="text-sm text-slate-500">{emptyText}</p>}
                {items.map((item) => (
                    <div key={item.key} className="rounded-lg border p-3 transition-colors hover:bg-slate-50 dark:bg-slate-700">
                        {item.content}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
