import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { ScanLine, MapPin, Star, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { publicApi } from "@/lib/api";

type RadiologyRow = {
  id?: string | number;
  name?: string;
  address?: string;
  description?: string;
  rating?: number | string;
};

export default function RadiologyCentersPage() {
  const [search, setSearch] = useState("");
  const centersQuery = useQuery({
    queryKey: ["public", "landing", "radiology-centers"],
    queryFn: () => publicApi.landingRadiologyCenters({ per_page: "60" }),
  });
  const centers = useMemo(() => {
    const root = (centersQuery.data as { data?: unknown } | undefined)?.data ?? centersQuery.data;
    if (Array.isArray(root)) return root as RadiologyRow[];
    return ((root as { data?: RadiologyRow[] } | undefined)?.data ?? []) as RadiologyRow[];
  }, [centersQuery.data]);
  const filtered = centers.filter((c) => String(c.name ?? "").toLowerCase().includes(search.toLowerCase()));

  return (
    <div className="container py-8 md:py-12">
      <h1 className="text-3xl font-bold">Radiology Centers</h1>
      <p className="text-muted-foreground mt-1 mb-8">Find imaging and radiology services</p>

      <div className="relative max-w-md mb-8">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search centers..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filtered.map((center, i) => (
          <motion.div key={String(center.id ?? i)} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <Link to={`/radiology-centers/${center.id ?? ""}`} className="group block">
              <div className="bg-card rounded-xl border shadow-card hover:shadow-elevated transition-shadow p-5">
                <div className="h-10 w-10 rounded-lg bg-sidebar-accent flex items-center justify-center mb-4">
                  <ScanLine className="h-5 w-5 text-primary" />
                </div>
                <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{center.name ?? "Radiology Center"}</h3>
                <p className="text-sm text-muted-foreground mt-1">{center.description ?? "Imaging and radiology services available"}</p>
                <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                  <MapPin className="h-3.5 w-3.5" /> {center.address ?? "Location not available"}
                </div>
                <div className="flex items-center gap-1 mt-2">
                  <Star className="h-4 w-4 text-warning fill-current" />
                  <span className="text-sm font-semibold">{center.rating ?? "0.0"}</span>
                </div>
              </div>
            </Link>
          </motion.div>
        ))}
        {centersQuery.isError && (
          <div className="col-span-full rounded-xl border border-destructive/30 bg-destructive/10 p-6 text-sm text-destructive">
            {centersQuery.error instanceof Error ? centersQuery.error.message : "Failed to load radiology centers."}
          </div>
        )}
      </div>
    </div>
  );
}
