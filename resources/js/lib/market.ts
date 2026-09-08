import type { Quote, Snapshot } from "../types/market";

export const compactNumber = (value: number) =>
    new Intl.NumberFormat("id-ID", {
        notation: "compact",
        maximumFractionDigits: 1,
    }).format(value);
export const leaders = (quotes: Quote[], limit = 3) => ({
    gainers: [...quotes]
        .sort((a, b) => b.change_percent - a.change_percent)
        .slice(0, limit),
    losers: [...quotes]
        .sort((a, b) => a.change_percent - b.change_percent)
        .slice(0, limit),
});
export const emptySnapshot: Snapshot = {
    quotes: [],
    as_of: new Date().toISOString(),
    source: "simulated",
    session_date: "",
};
