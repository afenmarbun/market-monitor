import { useConnectionStatus, useEchoPublic } from "@laravel/echo-react";
import { SearchIcon } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { Badge } from "./ui/badge";
import {
    Card,
    CardDescription,
    CardHeader,
    CardPanel,
    CardTitle,
} from "./ui/card";
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from "./ui/empty";
import { Input } from "./ui/input";
import { Tabs, TabsList, TabsTab } from "./ui/tabs";
import { MarketChart } from "../../../components/spectrumui/charts/market-chart";
import { MarketTable } from "./p-table-4";
import { Footer } from "./footer";
import { compactNumber, emptySnapshot, leaders } from "../lib/market";
import type {
    MarketIndex,
    MarketStatus,
    Quote,
    Snapshot,
} from "../types/market";

type Props = { initialSnapshot: Snapshot };

const marketChartRanges = [
    { label: "1D", bars: 2 },
    { label: "1W", bars: 7 },
    { label: "1M", bars: 30 },
    { label: "1Y", bars: null },
];

export function DashboardsMarket({ initialSnapshot }: Props) {
    const [snapshot, setSnapshot] = useState(initialSnapshot ?? emptySnapshot);
    const [indices, setIndices] = useState<MarketIndex[]>([]);
    const [providerStatus, setProviderStatus] = useState<MarketStatus | null>(
        null,
    );
    const [query, setQuery] = useState("");
    const connection = useConnectionStatus();
    const freshnessWindow =
        snapshot.source === "live"
            ? (providerStatus?.poll_seconds ?? 900) * 1000 + 60000
            : 15000;
    const fresh =
        !providerStatus?.fallback_active &&
        Date.now() - new Date(snapshot.as_of).getTime() < freshnessWindow;
    const sourceLabel =
        snapshot.source === "live" ? "Live" : "Live data unavailable";
    const filtered = useMemo(
        () =>
            snapshot.quotes
                .filter((quote) =>
                    `${quote.symbol} ${quote.name} ${quote.sector}`
                        .toLowerCase()
                        .includes(query.toLowerCase()),
                )
                .sort((a, b) => a.symbol.localeCompare(b.symbol)),
        [snapshot, query],
    );
    const ihsg = indices.find((item) => item.code === "COMPOSITE");
    const chartData = useMemo(() => toChartData(ihsg?.series ?? []), [ihsg]);

    useEffect(() => {
        const refresh = () =>
            Promise.all([
                fetch("/api/market/indices").then((response) =>
                    response.json(),
                ),
                fetch("/api/market/status").then((response) => response.json()),
            ])
                .then(([indexResponse, statusResponse]) => {
                    setIndices(indexResponse.indices ?? []);
                    setProviderStatus(statusResponse);
                })
                .catch(() => undefined);
        refresh();
        const timer = window.setInterval(refresh, 30000);
        return () => window.clearInterval(timer);
    }, []);
    useEchoPublic<Snapshot, "reverb">(
        "market",
        ".quotes.updated",
        setSnapshot,
        [setSnapshot],
    );
    useEffect(() => {
        const refresh = () =>
            fetch("/api/market/snapshot")
                .then((response) => response.json())
                .then(setSnapshot)
                .catch(() => undefined);
        const timer = window.setInterval(refresh, 15000);
        return () => window.clearInterval(timer);
    }, []);

    const statusVariant =
        !fresh || connection !== "connected"
            ? "warning"
            : snapshot.source === "live"
              ? "success"
              : "secondary";

    return (
        <div className="min-h-svh bg-background text-foreground">
            <header className="border-border border-b bg-card">
                <div className="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-4 py-4 md:px-6">
                    <div className="flex min-w-0 items-center gap-4">
                        <img
                            src="/idx-logo.png"
                            alt="IDX Stock Information"
                            className="h-10 w-auto shrink-0 object-contain"
                            decoding="async"
                        />
                        <div className="min-w-0">
                            <div className="font-heading text-base font-semibold leading-tight tracking-tight text-foreground">
                                Market Monitor
                            </div>
                            <div className="mt-1 text-[11px] font-medium leading-none tracking-[0.04em] text-muted-foreground">
                                Indonesia market dashboard
                            </div>
                        </div>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        <Badge variant={statusVariant} className="gap-1.5">
                            <span
                                className="size-1.5 rounded-full bg-current"
                                aria-hidden="true"
                            />
                            {connection !== "connected"
                                ? "Connecting"
                                : !fresh
                                  ? "Data delayed"
                                  : sourceLabel}
                        </Badge>
                    </div>
                </div>
            </header>
            <main className="mx-auto flex max-w-[1440px] flex-col gap-5 px-4 py-5 md:px-6 md:py-6">
                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,0.72fr)]">
                    <Card className="overflow-hidden">
                        <CardPanel className="p-4 md:p-6">
                            {chartData.length ? (
                                <MarketChart
                                    data={chartData}
                                    symbol="IHSG"
                                    name="Indonesia Composite"
                                    variant="area"
                                    showVolume={false}
                                    showRangeSelector
                                    ranges={marketChartRanges}
                                    defaultRange="1W"
                                    height={320}
                                    formatPrice={(value) =>
                                        value.toLocaleString("id-ID")
                                    }
                                />
                            ) : (
                                <Empty>
                                    <EmptyHeader>
                                        <EmptyTitle>
                                            IHSG history unavailable
                                        </EmptyTitle>
                                        <EmptyDescription>
                                            Index data will appear when the
                                            provider sends the latest history.
                                        </EmptyDescription>
                                    </EmptyHeader>
                                </Empty>
                            )}
                        </CardPanel>
                    </Card>
                    <MarketPulse quotes={snapshot.quotes} />
                </section>
                <section>
                    <Card className="overflow-hidden">
                        <CardHeader className="border-border/60 border-b p-4">
                            <div className="relative w-full">
                                <SearchIcon
                                    className="pointer-events-none absolute top-1/2 left-2.5 z-10 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    type="search"
                                    value={query}
                                    onChange={(event) =>
                                        setQuery(event.target.value)
                                    }
                                    placeholder="Search stocks..."
                                    aria-label="Search stocks"
                                    className="ps-8"
                                />
                            </div>
                        </CardHeader>
                        <CardPanel className="p-4">
                            <MarketTable quotes={filtered} />
                        </CardPanel>
                    </Card>
                </section>
            </main>
            <Footer />
        </div>
    );
}

type PulseTab = "gainers" | "losers" | "volume";

function MarketPulse({ quotes }: { quotes: Quote[] }) {
    const [tab, setTab] = useState<PulseTab>("gainers");
    const { gainers, losers } = leaders(quotes, 5);
    const volumeLeaders = [...quotes]
        .sort((a, b) => b.volume - a.volume)
        .slice(0, 5);
    const items =
        tab === "gainers" ? gainers : tab === "losers" ? losers : volumeLeaders;
    const subtitle =
        tab === "gainers"
            ? "Highest positive change"
            : tab === "losers"
              ? "Deepest decline"
              : "Highest trading volume";

    return (
        <Card>
            <CardHeader className="border-border/60 border-b py-4">
                <div className="flex flex-col gap-3">
                    <div>
                        <CardTitle>Top movers</CardTitle>
                        <CardDescription>{subtitle}</CardDescription>
                    </div>
                    <Tabs
                        value={tab}
                        onValueChange={(value) => setTab(value as PulseTab)}
                    >
                        <TabsList size="sm" className="w-full">
                            <TabsTab value="gainers" className="flex-1">
                                Gainer
                            </TabsTab>
                            <TabsTab value="losers" className="flex-1">
                                Loser
                            </TabsTab>
                            <TabsTab value="volume" className="flex-1">
                                Volume
                            </TabsTab>
                        </TabsList>
                    </Tabs>
                </div>
            </CardHeader>
            <CardPanel className="flex flex-col gap-2 p-3">
                {items.length ? (
                    items.map((quote, index) => (
                        <div
                            key={quote.symbol}
                            className="grid grid-cols-[24px_minmax(0,1fr)_80px_auto] items-center gap-2 rounded-lg border border-transparent px-2 py-2.5 transition-colors hover:border-border hover:bg-muted/40"
                        >
                            <span className="font-mono text-[10px] text-muted-foreground">
                                0{index + 1}
                            </span>
                            <div className="min-w-0">
                                <span className="block truncate font-mono text-sm font-medium">
                                    {quote.symbol}
                                </span>
                                <span className="block truncate text-xs text-muted-foreground">
                                    {quote.name}
                                </span>
                            </div>
                            <MiniTrend
                                values={quoteSeries(quote)}
                                positive={quote.change_percent >= 0}
                            />
                            <div
                                className={`text-right font-mono text-xs tabular-nums ${tab === "volume" ? "text-foreground" : quote.change_percent >= 0 ? "text-success-foreground" : "text-destructive"}`}
                            >
                                {tab === "volume"
                                    ? compactNumber(quote.volume)
                                    : `${quote.change_percent > 0 ? "+" : ""}${quote.change_percent}%`}
                            </div>
                        </div>
                    ))
                ) : (
                    <Empty className="py-8">
                        <EmptyHeader>
                            <EmptyTitle>No mover data available</EmptyTitle>
                        </EmptyHeader>
                    </Empty>
                )}
            </CardPanel>
        </Card>
    );
}

function quoteSeries(quote: Quote) {
    return [quote.previous_close, quote.price];
}
function toChartData(series: number[]) {
    return series.map((value, index) => {
        const previous = series[index - 1] ?? value;
        return {
            t: Date.now() - (series.length - 1 - index) * 86_400_000,
            open: previous,
            high: Math.max(previous, value),
            low: Math.min(previous, value),
            close: value,
            volume: 0,
        };
    });
}
function MiniTrend({
    values,
    positive,
}: {
    values: number[];
    positive: boolean;
}) {
    const width = 90;
    const height = 28;
    const max = Math.max(...values);
    const min = Math.min(...values);
    const range = Math.max(0.0001, max - min);
    const points = values
        .map(
            (value, index) =>
                `${(index / Math.max(1, values.length - 1)) * width},${height - ((value - min) / range) * (height - 4) - 2}`,
        )
        .join(" L ");
    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            className="h-7 w-20"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <path
                d={`M ${points}`}
                fill="none"
                stroke={positive ? "var(--success)" : "var(--destructive)"}
                strokeWidth="1.75"
                strokeLinecap="round"
            />
        </svg>
    );
}
