import { useQuery } from "@tanstack/react-query";
import { motion } from "framer-motion";
import { Building2, FlaskConical, ScanLine, Users, Shield, CalendarDays } from "lucide-react";
import { adminApi } from "@/lib/api";

export default function AdminDashboard() {
  const { data, isLoading } = useQuery({
    queryKey: ["admin", "dashboard", "cards"],
    queryFn: async () => {
      const [dashboard, clinics, labs, radiology, users, reviews] = await Promise.all([
        adminApi.dashboard(),
        adminApi.clinics({ per_page: "1" }),
        adminApi.medicalLabs({ per_page: "1" }),
        adminApi.radiologyCenters({ per_page: "1" }),
        adminApi.users({ per_page: "1" }),
        adminApi.reviews({ per_page: "5" }),
      ]);

      const dashboardRoot = (dashboard as { data?: Record<string, unknown> })?.data ?? {};
      const clinicsRoot = (clinics as { data?: { pagination?: { total?: number } } })?.data;
      const labsRoot = (labs as { data?: { pagination?: { total?: number } } })?.data;
      const radiologyRoot = (radiology as { data?: { pagination?: { total?: number } } })?.data;
      const usersRoot = (users as { data?: { pagination?: { total?: number } } })?.data;
      const reviewsRoot = (reviews as { data?: { data?: Array<Record<string, unknown>> } })?.data;

      return {
        doctorsCount: Number(dashboardRoot?.doctors_count ?? 0),
        reservationsCount: Number(dashboardRoot?.all_reservations_count ?? 0),
        clinicsCount: Number(clinicsRoot?.pagination?.total ?? 0),
        labsCount: Number(labsRoot?.pagination?.total ?? 0),
        radiologyCount: Number(radiologyRoot?.pagination?.total ?? 0),
        usersCount: Number(usersRoot?.pagination?.total ?? 0),
        recentReviews: reviewsRoot?.data ?? [],
      };
    },
  });

  const stats = [
    { label: "Total Clinics", value: data?.clinicsCount ?? "—", icon: Building2, color: "text-primary" },
    { label: "Medical Labs", value: data?.labsCount ?? "—", icon: FlaskConical, color: "text-secondary" },
    { label: "Radiology Centers", value: data?.radiologyCount ?? "—", icon: ScanLine, color: "text-accent" },
    { label: "Total Users", value: data?.usersCount ?? "—", icon: Users, color: "text-success" },
    { label: "Total Doctors", value: data?.doctorsCount ?? "—", icon: Shield, color: "text-warning" },
    { label: "Total Reservations", value: data?.reservationsCount ?? "—", icon: CalendarDays, color: "text-primary" },
  ];

  return (
    <div>
      <h2 className="text-2xl font-bold mb-6">Admin Dashboard</h2>

      <div className="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        {stats.map((stat, i) => (
          <motion.div
            key={stat.label}
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
            className="bg-card rounded-xl border p-5 shadow-card"
          >
            <div className="flex items-center justify-between mb-3">
              <stat.icon className={`h-5 w-5 ${stat.color}`} />
            </div>
            <p className="text-2xl font-bold">{isLoading ? "…" : String(stat.value)}</p>
            <p className="text-sm text-muted-foreground mt-1">{stat.label}</p>
          </motion.div>
        ))}
      </div>

      <div className="bg-card rounded-xl border shadow-card">
        <div className="p-5 border-b">
          <h3 className="font-semibold">Recent Reviews</h3>
        </div>
        <div className="divide-y">
          {(data?.recentReviews ?? []).map((item, i) => (
            <div key={String(item.id ?? i)} className="p-4 flex items-center gap-4">
              <div className="h-9 w-9 rounded-full bg-warning/10 flex items-center justify-center shrink-0">
                <Shield className="h-4 w-4 text-warning" />
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium text-sm">{String(item.patient_name ?? "Patient")}</p>
                <p className="text-xs text-muted-foreground">Rating: {String(item.rating ?? "—")} • {String(item.comment ?? "No comment")}</p>
              </div>
            </div>
          ))}
          {!isLoading && (data?.recentReviews?.length ?? 0) === 0 && (
            <div className="p-4 text-sm text-muted-foreground">No recent reviews.</div>
          )}
        </div>
      </div>
    </div>
  );
}
