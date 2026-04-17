import {
    ColumnDef,
    SortingState,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { Database, Loader2 } from 'lucide-react';
import { useState } from 'react';

interface Props<T extends object> {
    columns: ColumnDef<T>[];
    data: T[];
    onRowClick?: (row: T) => void;
    showRowCount?: boolean;
    showHeader?: boolean;
    clickableRows?: boolean;
    stripedRows?: boolean;
    emptyMessage?: string;
    loading?: boolean;
    loadingMessage?: string;
    skeletonRows?: number;
    stickyHeader?: boolean;
    compact?: boolean;
}

export default function DataTable<T extends object>({
    columns,
    data,
    onRowClick,
    showRowCount = true,
    showHeader = true,
    clickableRows = false,
    stripedRows = false,
    emptyMessage = 'No records found.',
    loading = false,
    loadingMessage = 'Loading data...',
    skeletonRows = 6,
    stickyHeader = false,
    compact = false,
}: Props<T>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const table = useReactTable({
        data,
        columns,
        state: { sorting },
        onSortingChange: setSorting,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    return (
        <div className="overflow-auto rounded-xl border border-border bg-card text-card-foreground shadow-sm">
            <table className="w-full text-sm">
                {showHeader && (
                    <thead className={stickyHeader ? 'sticky top-0 z-10' : ''}>
                        {table.getHeaderGroups().map((group) => (
                            <tr key={group.id} className="border-b border-border bg-muted/60 text-foreground">
                                {group.headers.map((header) => (
                                    <th key={header.id} className={`text-center ${compact ? 'px-3 py-2.5' : 'px-4 py-3'}`}>
                                        {header.isPlaceholder ? null : (
                                            <button
                                                className="inline-flex items-center gap-1 rounded px-1 py-0.5 text-xs font-semibold tracking-wide text-muted-foreground transition-colors hover:text-primary md:text-sm"
                                                onClick={header.column.getToggleSortingHandler()}
                                            >
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                            </button>
                                        )}
                                    </th>
                                ))}
                            </tr>
                        ))}
                    </thead>
                )}
                <tbody>
                    {loading && (
                        <tr>
                            <td colSpan={columns.length} className="px-4 py-8 text-center">
                                <div className="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    <span>{loadingMessage}</span>
                                </div>
                                <div className="mt-4 space-y-2">
                                    {Array.from({ length: skeletonRows }).map((_, idx) => (
                                        <div key={idx} className="h-9 w-full animate-pulse rounded-md bg-muted/60" />
                                    ))}
                                </div>
                            </td>
                        </tr>
                    )}
                    {!loading && table.getRowModel().rows.length === 0 && (
                        <tr>
                            <td colSpan={columns.length} className="px-3 py-10 text-center">
                                <div className="flex flex-col items-center gap-2 text-sm text-muted-foreground">
                                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-muted/70">
                                        <Database className="h-4 w-4" />
                                    </span>
                                    <span>{emptyMessage}</span>
                                </div>
                            </td>
                        </tr>
                    )}
                    {!loading &&
                        table.getRowModel().rows.map((row, index) => (
                        <tr
                            key={row.id}
                            className={`border-b border-border/80 transition-colors last:border-b-0 ${clickableRows ? 'cursor-pointer hover:bg-accent/60' : ''} ${
                                stripedRows && index % 2 === 1 ? 'bg-muted/30' : ''
                            }`}
                            onClick={() => (clickableRows ? onRowClick?.(row.original) : undefined)}
                        >
                            {row.getVisibleCells().map((cell) => (
                                <td key={cell.id} className={`text-center text-foreground/90 ${compact ? 'px-3 py-2.5' : 'px-4 py-3'}`}>
                                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            {showRowCount && !loading && <div className="border-t border-border/80 px-4 py-2 text-xs text-muted-foreground">Rows: {data.length}</div>}
        </div>
    );
}
