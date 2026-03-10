import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Heart, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";

type ChronicDisease = {
  id: number | string;
  name?: string | null;
  measure?: string | null;
  date?: string | null;
  notes?: string | null;
  reservation?: {
    id?: number | string;
    date?: string | null;
  };
};

export default function PatientChronicDiseases() {
  const { token } = useAuth();
  const [search, setSearch] = useState("");

  const { data, isLoading, error } = useQuery({
    queryKey: ["patient", "chronic-diseases", token],
    queryFn: () => patientApi.chronicDiseases(token!),
    enabled: Boolean(token),
  });

  const rows = useMemo<ChronicDisease[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as ChronicDisease[];
  }, [data]);

  const filtered = rows.filter((d) =>
    `${d.name ?? ""} ${d.measure ?? ""} ${d.notes ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">Chronic Diseases</h2>
          <p className="text-muted-foreground text-sm mt-1">Dynamic data from your backend</p>
        </div>
        <div className="relative w-full sm:w-64">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
        </div>
      </div>

      {isLoading && <div className="text-sm text-muted-foreground">Loading chronic diseases...</div>}
      {error && <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load chronic diseases"}</div>}

      {!isLoading && !error && (
        <div className="grid gap-4 md:grid-cols-2">
          {filtered.length === 0 && (
            <div className="rounded-xl border bg-card p-4 text-sm text-muted-foreground">No chronic diseases found.</div>
          )}
          {filtered.map((d) => (
            <div key={String(d.id)} className="bg-card rounded-xl border p-5">
              <div className="flex items-start gap-3 mb-3">
                <div className="h-10 w-10 rounded-full bg-destructive/10 flex items-center justify-center">
                  <Heart className="h-5 w-5 text-destructive" />
                </div>
                <div>
                  <h3 className="font-semibold">{d.name ?? `Disease #${d.id}`}</h3>
                  <p className="text-xs text-muted-foreground">Date: {d.date ?? d.reservation?.date ?? "—"}</p>
                </div>
              </div>
              <div className="text-sm space-y-1">
                <p><span className="text-muted-foreground">Measure:</span> {d.measure ?? "—"}</p>
                <p><span className="text-muted-foreground">Notes:</span> {d.notes ?? "—"}</p>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
