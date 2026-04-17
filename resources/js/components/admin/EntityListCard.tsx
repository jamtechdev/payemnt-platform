import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ReactNode } from 'react';

interface EntityListCardProps {
    title: string;
    emptyText: string;
    items: Array<{ key: string; content: ReactNode }>;
}

export default function EntityListCard({ title, emptyText, items }: EntityListCardProps) {
    return (
        <Card className="border-border bg-card transition-all duration-200 hover:border-primary/30 hover:shadow-md">
            <CardHeader>
                <CardTitle className="text-lg">{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {items.length === 0 && <p className="text-sm text-muted-foreground">{emptyText}</p>}
                {items.map((item) => (
                    <div key={item.key} className="rounded-xl border border-border/80 bg-background/60 p-4 transition-colors hover:bg-accent/40">
                        {item.content}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
