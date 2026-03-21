import { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { Building2, MapPin, Star, Search, Filter } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { publicApi } from "@/lib/api";

type ClinicRow = {
  id?: string | number;
  name?: string;
  specialty_name?: string;
  address?: string;
  rating?: number | string;
  reviews_count?: number;
};

export default function ClinicsPage() {
  const [search, setSearch] = useState("");
  const clinicsQuery = useQuery({
    queryKey: ["public", "landing", "clinics"],
    queryFn: () => publicApi.landingFeaturedClinics({ per_page: "60" }),
  });

  const clinics = useMemo(() => {
    const root = (clinicsQuery.data as { data?: unknown } | undefined)?.data ?? clinicsQuery.data;
    if (Array.isArray(root)) return root as ClinicRow[];
    return ((root as { data?: ClinicRow[] } | undefined)?.data ?? []) as ClinicRow[];
  }, [clinicsQuery.data]);

  const filtered = clinics.filter((c) => {
    const name = String(c.name ?? "").toLowerCase();
    const specialty = String(c.specialty_name ?? "").toLowerCase();
    const term = search.toLowerCase();
    return name.includes(term) || specialty.includes(term);
  });

  return (
    <div className="container py-8 md:py-12">
      <div className="mb-8">
        <h1 className="text-3xl font-bold">Find a Clinic</h1>
        <p className="text-muted-foreground mt-1">Browse and filter clinics by specialty, area, and rating</p>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-8">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search by name or specialty..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-10"
          />
        </div>
        <Button variant="outline" className="gap-2">
          <Filter className="h-4 w-4" /> Filters
        </Button>
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {clinicsQuery.isLoading && (
          Array.from({ length: 6 }).map((_, idx) => (
            <div key={`clinic-loading-${idx}`} className="bg-card rounded-xl border shadow-card overflow-hidden animate-pulse">
              <div className="h-36 bg-muted" />
              <div className="p-5 space-y-3">
                <div className="h-4 w-2/3 rounded bg-muted" />
                <div className="h-3 w-1/2 rounded bg-muted" />
              </div>
            </div>
          ))
        )}
        {filtered.map((clinic, i) => (
          <motion.div
            key={String(clinic.id ?? i)}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
          >
            <Link to={`/clinics/${clinic.id ?? ""}`} className="group block">
              <div className="bg-card rounded-xl border shadow-card hover:shadow-elevated transition-shadow overflow-hidden">
                <div className="h-36 bg-muted flex items-center justify-center">
                  <Building2 className="h-10 w-10 text-muted-foreground/30" />
                </div>
                <div className="p-5">
                  <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{clinic.name ?? "Clinic"}</h3>
                  <p className="text-sm text-muted-foreground mt-1">{clinic.specialty_name ?? "General care"}</p>
                  <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                    <MapPin className="h-3.5 w-3.5" />
                    {clinic.address ?? "Location not available"}
                  </div>
                  <div className="flex items-center justify-between mt-3">
                    <div className="flex items-center gap-1">
                      <Star className="h-4 w-4 text-warning fill-current" />
                      <span className="text-sm font-semibold">{clinic.rating ?? "0.0"}</span>
                      <span className="text-xs text-muted-foreground">({clinic.reviews_count ?? 0})</span>
                    </div>
                    <Button size="sm" variant="ghost" className="text-primary text-xs">
                      Book Now
                    </Button>
                  </div>
                </div>
              </div>
            </Link>
          </motion.div>
        ))}
        {!clinicsQuery.isLoading && !clinicsQuery.isError && filtered.length === 0 && (
          <div className="col-span-full rounded-xl border bg-card p-8 text-center text-muted-foreground">
            No clinics found.
          </div>
        )}
        {clinicsQuery.isError && (
          <div className="col-span-full rounded-xl border border-destructive/30 bg-destructive/10 p-6 text-sm text-destructive">
            {clinicsQuery.error instanceof Error ? clinicsQuery.error.message : "Failed to load clinics."}
          </div>
        )}
      </div>
    </div>
  );
}
