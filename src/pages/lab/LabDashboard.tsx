import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { CalendarDays, Users, DollarSign, Clock, FlaskConical } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { labApi } from "@/lib/api";

const defaultStats = [
  { label: "Today's Tests", value: "—", icon: FlaskConical, change: "", color: "text-primary" },
  { label: "Total Patients", value: "—", icon: Users, change: "", color: "text-secondary" },
  { label: "Revenue (Month)", value: "—", icon: DollarSign, change: "", color: "text-success" },
  { label: "Avg. Wait Time", value: "—", icon: Clock, change: "", color: "text-accent" },
];

const statusStyles: Record<string, string> = {
  completed: "bg-success/10 text-success",
  pending: "bg-warning/10 text-warning",
  "in-progress": "bg-secondary/10 text-secondary",
};

export default function LabDashboard() {
  const { data, isLoading } = useQuery({
    queryKey: ["lab", "dashboard"],
    queryFn: () => labApi.dashboard(),
  });

  const dashboardData = (data as { data?: Record<string, unknown> })?.data ?? (data as Record<string, unknown> | undefined);
  const apiStats = Array.isArray(dashboardData?.stats) ? (dashboardData.stats as Array<{ label?: string; value?: string; change?: string }>) : [];
  const stats = defaultStats.map((d, i) => ({ ...d, ...apiStats[i], value: apiStats[i]?.value ?? d.value, change: apiStats[i]?.change ?? d.change }));
  const recentTests = (dashboardData?.reservations ?? []) as Array<Record<string, unknown>>;

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">Dashboard</h2>
        <Link to="/lab-dashboard/reservations">
          <Button size="sm" className="gradient-primary text-primary-foreground border-0">View Reservations</Button>
        </Link>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {stats.map((stat, i) => (
          <motion.div key={stat.label} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }} className="bg-card rounded-xl border p-5 shadow-card">
            <div className="flex items-center justify-between mb-3">
              <stat.icon className={`h-5 w-5 ${stat.color}`} />
              {stat.change && <span className={`text-xs font-medium ${stat.change.startsWith('+') ? "text-success" : "text-destructive"}`}>{stat.change}</span>}
            </div>
            <p className="text-2xl font-bold">{isLoading ? "…" : stat.value}</p>
            <p className="text-sm text-muted-foreground mt-1">{stat.label}</p>
          </motion.div>
        ))}
      </div>

      <div className="bg-card rounded-xl border shadow-card">
        <div className="p-5 border-b flex items-center justify-between">
          <h3 className="font-semibold">Recent Tests</h3>
          <Link to="/lab-dashboard/reservations">
            <Button variant="ghost" size="sm" className="text-primary text-xs">View All</Button>
          </Link>
        </div>
        <div className="divide-y">
          {isLoading && <div className="p-4 text-sm text-muted-foreground">Loading...</div>}
          {!isLoading && recentTests.length === 0 && <div className="p-4 text-sm text-muted-foreground">No recent tests found.</div>}
          {recentTests.map((t, idx) => (
            <div key={String(t.id ?? idx)} className="p-4 flex items-center gap-4">
              <div className="h-9 w-9 rounded-full bg-sidebar-accent flex items-center justify-center shrink-0">
                <FlaskConical className="h-4 w-4 text-primary" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm">{String(t.patient ?? t.title ?? "—")}</p>
                <p className="text-xs text-muted-foreground">{String(t.test ?? t.title ?? "—")}</p>
              </div>
              <span className="text-sm text-muted-foreground hidden sm:block">{String(t.time ?? "—")}</span>
              <Badge variant="secondary" className={statusStyles[String(t.status ?? "pending")] || ""}>{String(t.status ?? "pending")}</Badge>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
