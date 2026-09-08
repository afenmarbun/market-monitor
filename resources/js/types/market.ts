export type Quote = {
    symbol: string;
    name: string;
    sector: string;
    price: number;
    previous_close: number;
    change: number;
    change_percent: number;
    bid: number | null;
    ask: number | null;
    value: number | null;
    lot: number | null;
    frequency: number | null;
    average: number | null;
    open: number | null;
    high: number | null;
    low: number | null;
    volume: number;
    source: MarketSource;
    quoted_at: string | null;
};
export type MarketSource = "live" | "unavailable";
export type Snapshot = {
    quotes: Quote[];
    as_of: string;
    source: MarketSource;
    session_date: string;
};
export type MarketIndex = {
    code: string;
    value: number;
    previous: number;
    change: number;
    change_percent: number;
    updated_at: string;
    source: MarketSource;
    series: number[];
};
export type MarketStatus = {
    provider: string;
    configured: boolean;
    market_open: boolean;
    poll_seconds: number;
    last_success_at: string | null;
    last_failure_at: string | null;
    last_error: string | null;
    fallback_active: boolean;
};
