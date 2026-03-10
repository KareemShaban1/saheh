import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { ScanLine, Eye, Search, Image } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";
import { useLanguage} from "@/contexts/LanguageContext"

interface Ray {
  id: string | number;		
  date?: string;
  status?: string;
  ray_type?: string;
  body_part?: string;
  notes?: string;
  images?: string[];
  services?: Array<{ id?: number; service_name?: string; fee?: number }>;
}

const statusStyles: Record<string, string> = {
  ready: "bg-success/10 text-success border-success/20",
  pending: "bg-warning/10 text-warning border-warning/20",
  processing: "bg-secondary/10 text-secondary border-secondary/20",
  completed: "bg-success/10 text-success border-success/20",
};

export default function PatientRays() {
  const { t } = useLanguage();	
  const { token } = useAuth();
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState<Ray | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["patient", "rays", token],
    queryFn: () => patientApi.rays(token!),
    enabled: !!token,
  });

  const rays = useMemo<Ray[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as Ray[];
  }, [data]);

  const filtered = rays.filter((r) =>
    `${r.ray_type ?? ""} ${r.body_part ?? ""} ${r.status ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">{t("patient.menu.rays")}</h2>
          <p className="text-muted-foreground text-sm mt-1">Dynamic data from your backend</p>
        </div>
        <div className="relative w-full sm:w-64">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Search..." value={search} onChange={e => setSearch(e.target.value)} className="pl-9" />
        </div>
      </div>

      <div className="bg-card rounded-xl border overflow-hidden">
        <div className="hidden md:block">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Ray Type</TableHead>
              <TableHead>Body Part</TableHead>
              <TableHead className="hidden sm:table-cell">Date</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading && (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground">Loading rays...</TableCell>
              </TableRow>
            )}
            {error && (
              <TableRow>
                <TableCell colSpan={5} className="text-destructive">{error instanceof Error ? error.message : "Failed to load rays"}</TableCell>
              </TableRow>
            )}
            {!isLoading && !error && filtered.length === 0 && (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground">No rays found.</TableCell>
              </TableRow>
            )}
            {filtered.map((r) => (
              <TableRow key={String(r.id)} className="border-b last:border-0">
                <TableCell className="font-medium">{r.ray_type ?? `Ray #${r.id}`}</TableCell>
                <TableCell className="text-sm">{r.body_part ?? "—"}</TableCell>
                <TableCell className="hidden sm:table-cell text-sm">{r.date ?? "—"}</TableCell>
                <TableCell><Badge variant="outline" className={statusStyles[(r.status ?? "").toLowerCase()] ?? ""}>{r.status ?? "ready"}</Badge></TableCell>
                <TableCell className="text-right">
                  <button title="View ray" aria-label="View ray" className="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-muted" onClick={() => setSelected(r)}><Eye className="h-4 w-4" /></button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        </div>
        {isLoading && <div className="md:hidden p-4 text-sm text-muted-foreground">Loading rays...</div>}
        {error && <div className="md:hidden p-4 text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load rays"}</div>}
        {!isLoading && !error && (
          <div className="md:hidden divide-y">
            {filtered.map((r) => (
              <div key={String(r.id)} className="p-4 space-y-2">
                <div className="flex items-center justify-between gap-2">
                  <p className="font-medium">{r.ray_type ?? `Ray #${r.id}`}</p>
                  <Badge variant="outline" className={statusStyles[(r.status ?? "").toLowerCase()] ?? ""}>{r.status ?? "ready"}</Badge>
                </div>
                <p className="text-xs text-muted-foreground">{r.body_part ?? "—"} - {r.date ?? "—"}</p>
                <button
                  title="View ray"
                  aria-label="View ray"
                  className="inline-flex w-full h-9 items-center justify-center gap-2 rounded-md border hover:bg-muted text-sm"
                  onClick={() => setSelected(r)}
                >
                  <Eye className="h-4 w-4" />
                  View Details
                </button>
              </div>
            ))}
            {filtered.length === 0 && <div className="p-4 text-sm text-muted-foreground">No rays found.</div>}
          </div>
        )}
      </div>

      <Dialog open={!!selected} onOpenChange={() => setSelected(null)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><ScanLine className="h-5 w-5 text-primary" /> {selected?.ray_type ?? `Ray #${selected?.id}`} — {selected?.body_part ?? "—"}</DialogTitle>
          </DialogHeader>
          {selected && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div><span className="text-muted-foreground">Date:</span> <span className="font-medium">{selected.date ?? "—"}</span></div>
                <div><span className="text-muted-foreground">Status:</span> <span className="font-medium">{selected.status ?? "ready"}</span></div>
              </div>
              <div className="bg-muted/30 rounded-lg p-4 flex items-center justify-center h-40 border border-dashed">
                <div className="text-center text-muted-foreground">
                  <Image className="h-8 w-8 mx-auto mb-2" />
                  <p className="text-sm">{(selected.images ?? []).length > 0 ? `${selected.images?.length} image(s) attached` : "No images attached"}</p>
                </div>
              </div>
              <div>
                <h4 className="font-semibold mb-1 text-sm">Services</h4>
                <div className="space-y-1">
                  {(selected.services ?? []).map((service) => (
                    <p className="text-sm text-muted-foreground" key={String(service.id)}>
                      {service.service_name ?? `Service ${service.id}`} {typeof service.fee === "number" ? `- ${service.fee}` : ""}
                    </p>
                  ))}
                  {(selected.services ?? []).length === 0 && <p className="text-sm text-muted-foreground">No services attached.</p>}
                </div>
              </div>
              <div>
                <h4 className="font-semibold mb-1 text-sm">Notes</h4>
                <p className="text-sm text-muted-foreground">{selected.notes ?? "—"}</p>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
