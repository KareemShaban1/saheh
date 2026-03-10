import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { CalendarDays, Users, DollarSign, Clock, ScanLine } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { radiologyApi } from "@/lib/api";

const statusStyles: Record<string, string> = {
  completed: "bg-success/10 text-success",
  pending: "bg-warning/10 text-warning",
  "in-progress": "bg-secondary/10 text-secondary",
};

export default function RadiologyDashboard() {
  const dashboardQuery = useQuery({
    queryKey: ["radiology", "dashboard"],
    queryFn: () => radiologyApi.dashboard(),
  });
  const root = (dashboardQuery.data as { data?: unknown })?.data ?? dashboardQuery.data;
  const payload = (root && typeof root === "object" ? root : {}) as {
    stats?: Array<{ label?: string; value?: string | number }>;
    reservations?: Array<{ id?: string | number; patient_name?: string; title?: string; time?: string; status?: string }>;
  };

  const stats = useMemo(() => {
    const icons = [ScanLine, Users, DollarSign, Clock] as const;
    const colors = ["text-primary", "text-secondary", "text-success", "text-accent"];
    const fallback = [
      { label: "Today's Scans", value: "0" },
      { label: "Total Patients", value: "0" },
      { label: "Revenue (Month)", value: "EGP 0" },
      { label: "Avg. Wait Time", value: "0 min" },
    ];
    const src = Array.isArray(payload.stats) && payload.stats.length > 0 ? payload.stats : fallback;
    return src.slice(0, 4).map((item, i) => ({
      label: String(item.label ?? fallback[i]?.label ?? "Metric"),
      value: String(item.value ?? "0"),
      icon: icons[i] ?? ScanLine,
      change: "0%",
      color: colors[i] ?? "text-primary",
    }));
  }, [payload.stats]);

  const recentScans = useMemo(() => {
    const src = Array.isArray(payload.reservations) ? payload.reservations : [];
    return src.map((item) => ({
      id: String(item.id ?? "—"),
      patient: String(item.patient_name ?? "Patient"),
      scan: String(item.title ?? "Scan"),
      time: String(item.time ?? "—"),
      status: String(item.status ?? "pending"),
    }));
  }, [payload.reservations]);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">Dashboard</h2>
        <Button size="sm" className="gradient-primary text-primary-foreground border-0">+ New Scan</Button>
      </div>

      {dashboardQuery.isLoading ? <div className="text-sm text-muted-foreground mb-4">Loading dashboard...</div> : null}
      {dashboardQuery.error ? <div className="text-sm text-destructive mb-4">{dashboardQuery.error instanceof Error ? dashboardQuery.error.message : "Failed to load dashboard"}</div> : null}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {stats.map((stat, i) => (
          <motion.div key={stat.label} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }} className="bg-card rounded-xl border p-5 shadow-card">
            <div className="flex items-center justify-between mb-3">
              <stat.icon className={`h-5 w-5 ${stat.color}`} />
              <span className={`text-xs font-medium ${stat.change.startsWith('+') ? 'text-success' : 'text-destructive'}`}>{stat.change}</span>
            </div>
            <p className="text-2xl font-bold">{stat.value}</p>
            <p className="text-sm text-muted-foreground mt-1">{stat.label}</p>
          </motion.div>
        ))}
      </div>

      <div className="bg-card rounded-xl border shadow-card">
        <div className="p-5 border-b flex items-center justify-between">
          <h3 className="font-semibold">Recent Scans</h3>
          <Button variant="ghost" size="sm" className="text-primary text-xs">View All</Button>
        </div>
        <div className="divide-y">
          {recentScans.map((s) => (
            <div key={s.id} className="p-4 flex items-center gap-4">
              <div className="h-9 w-9 rounded-full bg-sidebar-accent flex items-center justify-center shrink-0">
                <ScanLine className="h-4 w-4 text-primary" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm">{s.patient}</p>
                <p className="text-xs text-muted-foreground">{s.scan}</p>
              </div>
              <span className="text-sm text-muted-foreground hidden sm:block">{s.time}</span>
              <Badge variant="secondary" className={statusStyles[s.status] || ""}>{s.status}</Badge>
            </div>
          ))}
          {recentScans.length === 0 ? (
            <div className="p-4 text-sm text-muted-foreground">No recent scans found.</div>
          ) : null}
        </div>
      </div>
    </div>
  );
}
