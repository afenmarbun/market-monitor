"use client";

import {
    type ColumnDef,
    createPaginatedRowModel,
    createSortedRowModel,
    type PaginationState,
    rowPaginationFeature,
    rowSortingFeature,
    type SortingState,
    tableFeatures,
    useTable,
} from "@tanstack/react-table";
import { ChevronDownIcon, ChevronUpIcon } from "lucide-react";
import { useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Frame, FrameFooter } from "@/components/ui/frame";
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination";
import {
    Select,
    SelectItem,
    SelectPopup,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import type { Quote } from "@/types/market";

type Props = {
    quotes: Quote[];
};

const features = tableFeatures({
    paginatedRowModel: createPaginatedRowModel(),
    rowPaginationFeature,
    rowSortingFeature,
    sortedRowModel: createSortedRowModel(),
});

const columnClasses: Record<string, string> = {
    symbol: "min-w-[190px]",
    price: "min-w-[100px]",
    change_percent: "min-w-[210px]",
    bid: "min-w-[100px]",
    ask: "min-w-[100px]",
    value: "min-w-[120px]",
    lot: "min-w-[110px]",
    frequency: "min-w-[105px]",
    previous_close: "min-w-[100px]",
    open: "min-w-[100px]",
    high: "min-w-[100px]",
    low: "min-w-[100px]",
};

export function MarketTable({ quotes }: Props) {
    const [pagination, setPagination] = useState<PaginationState>({
        pageIndex: 0,
        pageSize: 8,
    });
    const [sorting, setSorting] = useState<SortingState>([]);
    const columns = useMemo<ColumnDef<typeof features, Quote>[]>(
        () => [
            {
                accessorKey: "symbol",
                header: "Symbol",
                cell: ({ row }) => (
                    <div>
                        <div className="font-mono font-medium">
                            {row.original.symbol}
                        </div>
                        <div className="max-w-44 truncate text-xs text-muted-foreground">
                            {row.original.name} · {row.original.sector}
                        </div>
                    </div>
                ),
                size: 190,
            },
            {
                accessorKey: "price",
                header: "Price",
                cell: ({ row }) => (
                    <span className="font-mono tabular-nums">
                        {formatNumber(row.original.price)}
                    </span>
                ),
                size: 110,
            },
            {
                accessorKey: "change_percent",
                header: "Change (%)",
                cell: ({ row }) => (
                    <span
                        className={
                            row.original.change >= 0
                                ? "font-mono tabular-nums text-success-foreground"
                                : "font-mono tabular-nums text-destructive"
                        }
                    >
                        {row.original.change > 0
                            ? "↗"
                            : row.original.change < 0
                              ? "↘"
                              : "0"}{" "}
                        {formatNumber(Math.abs(row.original.change))} (
                        {row.original.change_percent > 0 ? "+" : ""}
                        {row.original.change_percent}%)
                    </span>
                ),
                size: 156,
            },
            {
                accessorKey: "bid",
                header: "Bid",
                cell: ({ row }) => (
                    <span className="font-mono tabular-nums">
                        {formatNumber(row.original.bid)}
                    </span>
                ),
                size: 100,
            },
            {
                accessorKey: "ask",
                header: "Ask",
                cell: ({ row }) => (
                    <span className="font-mono tabular-nums text-success-foreground">
                        {formatNumber(row.original.ask)}
                    </span>
                ),
                size: 100,
            },
            {
                accessorKey: "value",
                header: "Value",
                cell: ({ row }) => (
                    <span className="font-mono text-xs tabular-nums">
                        {formatCompactMarket(row.original.value)}
                    </span>
                ),
                size: 108,
            },
            {
                accessorKey: "lot",
                header: "Lot",
                cell: ({ row }) => (
                    <span className="font-mono text-xs tabular-nums">
                        {formatCompactMarket(row.original.lot)}
                    </span>
                ),
                size: 100,
            },
            {
                accessorKey: "frequency",
                header: "Freq",
                cell: ({ row }) => (
                    <span className="font-mono text-xs text-chart-2 tabular-nums">
                        {formatCompactMarket(row.original.frequency)}
                    </span>
                ),
                size: 96,
            },
            {
                accessorKey: "previous_close",
                header: "Prev",
                cell: ({ row }) => (
                    <span className="font-mono tabular-nums">
                        {formatNumber(row.original.previous_close)}
                    </span>
                ),
                size: 100,
            },
            {
                accessorKey: "open",
                header: "Open",
                cell: ({ row }) => (
                    <span
                        className={`font-mono tabular-nums ${row.original.open !== null && row.original.open >= row.original.previous_close ? "text-success-foreground" : ""}`}
                    >
                        {formatNumber(row.original.open)}
                    </span>
                ),
                size: 100,
            },
            {
                accessorKey: "high",
                header: "High",
                cell: ({ row }) => (
                    <span className="font-mono tabular-nums text-success-foreground">
                        {formatNumber(row.original.high)}
                    </span>
                ),
                size: 100,
            },
            {
                accessorKey: "low",
                header: "Low",
                cell: ({ row }) => (
                    <span className="font-mono tabular-nums text-destructive">
                        {formatNumber(row.original.low)}
                    </span>
                ),
                size: 100,
            },
        ],
        [],
    );
    const table = useTable({
        columns,
        data: quotes,
        enableSortingRemoval: false,
        getRowId: (row) => row.symbol,
        onPaginationChange: setPagination,
        onSortingChange: setSorting,
        features,
        state: { pagination, sorting },
    });

    const pageRanges = Array.from(
        { length: table.getPageCount() },
        (_, index) => {
            const start = index * table.state.pagination.pageSize + 1;
            const end = Math.min(
                (index + 1) * table.state.pagination.pageSize,
                table.getRowCount(),
            );
            return { label: `${start}-${end}`, value: index + 1 };
        },
    );

    return (
        <Frame className="w-full">
            <Table variant="card" className="min-w-[1420px] table-auto">
                <TableHeader>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <TableRow
                            className="hover:bg-transparent"
                            key={headerGroup.id}
                        >
                            {headerGroup.headers.map((header) => (
                                <TableHead
                                    className={columnClasses[header.column.id]}
                                    key={header.id}
                                >
                                    {header.isPlaceholder ? null : header.column.getCanSort() ? (
                                        <button
                                            type="button"
                                            className="flex h-full w-full cursor-pointer select-none items-center justify-between gap-2 text-left"
                                            onClick={header.column.getToggleSortingHandler()}
                                        >
                                            {table.FlexRender({ header })}
                                            {{
                                                asc: (
                                                    <ChevronUpIcon
                                                        aria-hidden="true"
                                                        className="size-4 shrink-0 opacity-80"
                                                    />
                                                ),
                                                desc: (
                                                    <ChevronDownIcon
                                                        aria-hidden="true"
                                                        className="size-4 shrink-0 opacity-80"
                                                    />
                                                ),
                                            }[
                                                header.column.getIsSorted() as string
                                            ] ?? null}
                                        </button>
                                    ) : (
                                        table.FlexRender({ header })
                                    )}
                                </TableHead>
                            ))}
                        </TableRow>
                    ))}
                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows.length ? (
                        table.getRowModel().rows.map((row) => (
                            <TableRow key={row.id}>
                                {row.getAllCells().map((cell) => (
                                    <TableCell
                                        className={
                                            columnClasses[cell.column.id]
                                        }
                                        key={cell.id}
                                    >
                                        {table.FlexRender({ cell })}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell
                                className="h-24 text-center"
                                colSpan={columns.length}
                            >
                                No matching stocks.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
            <FrameFooter className="p-2">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-center gap-2 whitespace-nowrap">
                        <p className="text-muted-foreground text-sm">Viewing</p>
                        <Select
                            items={pageRanges}
                            onValueChange={(value) =>
                                table.setPageIndex(Number(value) - 1)
                            }
                            value={table.state.pagination.pageIndex + 1}
                        >
                            <SelectTrigger
                                aria-label="Select result range"
                                className="w-fit min-w-16"
                                size="sm"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectPopup>
                                {pageRanges.map((item) => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectPopup>
                        </Select>
                        <p className="text-muted-foreground text-sm">
                            of{" "}
                            <strong className="font-medium text-foreground">
                                {table.getRowCount()}
                            </strong>{" "}
                            results
                        </p>
                    </div>
                    <Pagination className="mx-0 w-auto justify-end">
                        <PaginationContent>
                            <PaginationItem>
                                <PaginationPrevious
                                    className="sm:*:[svg]:hidden"
                                    render={
                                        <Button
                                            type="button"
                                            disabled={
                                                !table.getCanPreviousPage()
                                            }
                                            onClick={() => table.previousPage()}
                                            size="sm"
                                            variant="outline"
                                        />
                                    }
                                />
                            </PaginationItem>
                            <PaginationItem>
                                <PaginationNext
                                    className="sm:*:[svg]:hidden"
                                    render={
                                        <Button
                                            type="button"
                                            disabled={!table.getCanNextPage()}
                                            onClick={() => table.nextPage()}
                                            size="sm"
                                            variant="outline"
                                        />
                                    }
                                />
                            </PaginationItem>
                        </PaginationContent>
                    </Pagination>
                </div>
            </FrameFooter>
        </Frame>
    );
}

function formatNumber(value: number | null): string {
    return value === null ? "-" : value.toLocaleString("id-ID");
}

function formatCompactMarket(value: number | null): string {
    return value === null
        ? "-"
        : new Intl.NumberFormat("en-US", {
              notation: "compact",
              maximumFractionDigits: 2,
          }).format(value);
}

export default MarketTable;
