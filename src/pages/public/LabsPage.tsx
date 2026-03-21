import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { FlaskConical, MapPin, Star, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { publicApi } from "@/lib/api";

type LabRow = {
  id?: string | number;
  name?: string;
  address?: string;
  description?: string;
  rating?: number | string;
};

export default function LabsPage() {
  const [search, setSearch] = useState("");
  const labsQuery = useQuery({
    queryKey: ["public", "landing", "labs"],
    queryFn: () => publicApi.landingMedicalLabs({ per_page: "60" }),
  });
  const labs = useMemo(() => {
    const root = (labsQuery.data as { data?: unknown } | undefined)?.data ?? labsQuery.data;
    if (Array.isArray(root)) return root as LabRow[];
    return ((root as { data?: LabRow[] } | undefined)?.data ?? []) as LabRow[];
  }, [labsQuery.data]);
  const filtered = labs.filter((l) => String(l.name ?? "").toLowerCase().includes(search.toLowerCase()));

  return (
    <div className="container py-8 md:py-12">
      <h1 className="text-3xl font-bold">Medical Laboratories</h1>
      <p className="text-muted-foreground mt-1 mb-8">Find accredited labs for your medical tests</p>

      <div className="relative max-w-md mb-8">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search labs..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filtered.map((lab, i) => (
          <motion.div key={String(lab.id ?? i)} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <Link to={`/labs/${lab.id ?? ""}`} className="group block">
              <div className="bg-card rounded-xl border shadow-card hover:shadow-elevated transition-shadow p-5">
                <div className="h-10 w-10 rounded-lg bg-sidebar-accent flex items-center justify-center mb-4">
                  <FlaskConical className="h-5 w-5 text-primary" />
                </div>
                <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{lab.name ?? "Medical Lab"}</h3>
                <p className="text-sm text-muted-foreground mt-1">{lab.description ?? "Laboratory services available"}</p>
                <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                  <MapPin className="h-3.5 w-3.5" /> {lab.address ?? "Location not available"}
                </div>
                <div className="flex items-center gap-1 mt-2">
                  <Star className="h-4 w-4 text-warning fill-current" />
                  <span className="text-sm font-semibold">{lab.rating ?? "0.0"}</span>
                </div>
              </div>
            </Link>
          </motion.div>
        ))}
        {labsQuery.isError && (
          <div className="col-span-full rounded-xl border border-destructive/30 bg-destructive/10 p-6 text-sm text-destructive">
            {labsQuery.error instanceof Error ? labsQuery.error.message : "Failed to load medical laboratories."}
          </div>
        )}
      </div>
    </div>
  );
}
