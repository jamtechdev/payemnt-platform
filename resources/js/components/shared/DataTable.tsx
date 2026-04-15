import {
    ColumnDef,
    SortingState,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
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
        <div className="rounded border bg-white">
            <table className="w-full text-sm">
                {showHeader && (
                    <thead>
                        {table.getHeaderGroups().map((group) => (
                            <tr key={group.id} className="border-b">
                                {group.headers.map((header) => (
                                    <th key={header.id} className="px-3 py-2 text-left">
                                        {header.isPlaceholder ? null : (
                                            <button className="inline-flex items-center gap-1" onClick={header.column.getToggleSortingHandler()}>
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
                    {table.getRowModel().rows.length === 0 && (
                        <tr>
                            <td colSpan={columns.length} className="px-3 py-6 text-center text-sm text-neutral-500">
                                {emptyMessage}
                            </td>
                        </tr>
                    )}
                    {table.getRowModel().rows.map((row, index) => (
                        <tr
                            key={row.id}
                            className={`border-b ${clickableRows ? 'cursor-pointer hover:bg-neutral-50' : ''} ${stripedRows && index % 2 === 1 ? 'bg-slate-50/70' : ''}`}
                            onClick={() => (clickableRows ? onRowClick?.(row.original) : undefined)}
                        >
                            {row.getVisibleCells().map((cell) => (
                                <td key={cell.id} className="px-3 py-2">
                                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            {showRowCount && <div className="px-3 py-2 text-xs text-neutral-500">Rows: {data.length}</div>}
        </div>
    );
}
