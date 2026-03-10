import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { CalendarDays, Users, DollarSign, Clock } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { clinicApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

const statusStyles: Record<string, string> = {
  confirmed: "bg-success/10 text-success",
  pending: "bg-warning/10 text-warning",
  "in-progress": "bg-secondary/10 text-secondary",
};

export default function ClinicDashboardPage() {
  const { t } = useLanguage();
  const defaultStats = [
    { label: t("clinic.dashboard.TodayReservations"), value: "—", icon: CalendarDays, change: "", color: "text-primary" },
    { label: t("clinic.dashboard.TotalPatients"), value: "—", icon: Users, change: "", color: "text-secondary" },
    { label: t("clinic.dashboard.RevenueMonth"), value: "—", icon: DollarSign, change: "", color: "text-success" },
    { label: t("clinic.dashboard.AvgWaitTime"), value: "—", icon: Clock, change: "", color: "text-accent" },
  ];
  const { data, isLoading, error, isError } = useQuery({
    queryKey: ["clinic", "dashboard"],
    queryFn: () => clinicApi.dashboard(),
  });

  const dashboardData = (data as { data?: Record<string, unknown> })?.data ?? data as Record<string, unknown> | undefined;
  const apiStats = Array.isArray(dashboardData?.stats) ? dashboardData.stats as Array<{ label?: string; value?: string; change?: string }> : [];
  const stats = defaultStats.map((d, i) => ({ ...d, ...apiStats[i], value: apiStats[i]?.value ?? d.value, change: apiStats[i]?.change ?? d.change }));
  const recentReservations = (dashboardData?.reservations ?? dashboardData?.recent ?? []) as Array<Record<string, unknown>>;

  if (isError && (error as { message?: string })?.message?.toLowerCase().includes("401")) {
    return (
      <div className="rounded-xl border bg-card p-6 text-center">
        <p className="text-muted-foreground mb-4">{t("clinic.dashboard.YouMustBeSignedInToViewTheClinicDashboard")}</p>
        <Link to="/dashboard-login">
          <Button className="gradient-primary border-0">{t("clinic.dashboard.SignIn")}</Button>
        </Link>
      </div>
    );
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold"> {t("clinic.dashboard.Dashboard")}</h2>
        <Button size="sm" className="gradient-primary text-primary-foreground border-0">
          + {t("clinic.dashboard.AddReservation")}
        </Button>
      </div>

      <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {stats.map((stat, i) => (
          <motion.div
            key={stat.label}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
            className="bg-card rounded-xl border p-5 shadow-card"
          >
            <div className="flex items-center justify-between mb-3">
              <stat.icon className={`h-5 w-5 ${stat.color ?? "text-primary"}`} />
              {stat.change && (
                <span className={`text-xs font-medium ${String(stat.change).startsWith("+") ? "text-success" : "text-destructive"}`}>
                  {stat.change}
                </span>
              )}
            </div>
            <p className="text-2xl font-bold">{isLoading ? "…" : String(stat.value)}</p>
            <p className="text-sm text-muted-foreground mt-1">{stat.label}</p>
          </motion.div>
        ))}
      </div>

      <div className="bg-card rounded-xl border shadow-card">
        <div className="p-5 border-b flex items-center justify-between">
          <h3 className="font-semibold"> {t("clinic.dashboard.TodayReservations")}</h3>
          <Link to="/clinic-dashboard/reservations">
            <Button variant="ghost" size="sm" className="text-primary text-xs">{t("clinic.dashboard.ViewAll")}</Button>
          </Link>
        </div>
        <div className="divide-y">
          {isLoading && <div className="p-4 text-muted-foreground text-sm">Loading…</div>}
          {!isLoading && recentReservations.length === 0 && (
            <div className="p-4 text-muted-foreground text-sm">{t("clinic.dashboard.NoReservationsToday")}</div>
          )}
          {recentReservations.map((res, idx) => (
            <div key={String(res.id ?? idx)} className="p-4 flex items-center gap-4">
              <div className="h-9 w-9 rounded-full bg-sidebar-accent flex items-center justify-center shrink-0">
                <Users className="h-4 w-4 text-primary" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm">{String(res.patient_name ?? res.patient ?? "—")}</p>
                <p className="text-xs text-muted-foreground">{String(res.doctor_name ?? res.doctor ?? "—")}</p>
              </div>
              <span className="text-sm text-muted-foreground hidden sm:block">{String(res.time ?? res.reservation_time ?? "—")}</span>
              <Badge variant="secondary" className={statusStyles[String(res.status ?? "pending")] ?? ""}>
                {String(res.status ?? "pending")}
              </Badge>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
