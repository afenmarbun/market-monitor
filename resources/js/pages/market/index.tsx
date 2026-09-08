import type { Snapshot } from "../../types/market";
import { DashboardsMarket } from "../../components/dashboards-market";

export default function MarketIndex({ snapshot }: { snapshot: Snapshot }) {
    return <DashboardsMarket initialSnapshot={snapshot} />;
}
