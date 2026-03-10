import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { FlaskConical, Eye, Search } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";

interface Analysis {
  id: string | number;
  date?: string;
  status?: string;
  report_type?: string;
  notes?: string;
  reservation?: { id?: number; doctor_id?: number; date?: string };
  lab_service_options?: Array<{ id?: number; name?: string; option_name?: string; price?: number }>;
}

const statusStyles: Record<string, string> = {
  ready: "bg-success/10 text-success border-success/20",
  pending: "bg-warning/10 text-warning border-warning/20",
  processing: "bg-secondary/10 text-secondary border-secondary/20",
  completed: "bg-success/10 text-success border-success/20",
};

export default function PatientAnalyses() {
  const { token } = useAuth();
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState<Analysis | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["patient", "medical-analyses", token],
    queryFn: () => patientApi.medicalAnalyses(token!),
    enabled: !!token,
  });

  const analyses = useMemo<Analysis[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as Analysis[];
  }, [data]);

  const filtered = analyses.filter((a) =>
    `${a.report_type ?? ""} ${a.notes ?? ""} ${a.status ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">Medical Analyses</h2>
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
              <TableHead>Test Type</TableHead>
              <TableHead className="hidden sm:table-cell">Date</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading && (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground">Loading analyses...</TableCell>
              </TableRow>
            )}
            {error && (
              <TableRow>
                <TableCell colSpan={5} className="text-destructive">{error instanceof Error ? error.message : "Failed to load analyses"}</TableCell>
              </TableRow>
            )}
            {!isLoading && !error && filtered.length === 0 && (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground">No analyses found.</TableCell>
              </TableRow>
            )}
            {filtered.map((a, i) => (
              <TableRow key={String(a.id)} className="border-b last:border-0">
                <TableCell className="font-medium">{a.report_type ?? `Analysis #${a.id}`}</TableCell>
                <TableCell className="hidden sm:table-cell text-sm">{a.date ?? a.reservation?.date ?? "—"}</TableCell>
                <TableCell><Badge variant="outline" className={statusStyles[(a.status ?? "").toLowerCase()] ?? ""}>{a.status ?? "ready"}</Badge></TableCell>
                <TableCell className="text-right">
                  <button title="View analysis" aria-label="View analysis" className="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-muted" onClick={() => setSelected(a)}><Eye className="h-4 w-4" /></button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
        </div>
        {isLoading && <div className="md:hidden p-4 text-sm text-muted-foreground">Loading analyses...</div>}
        {error && <div className="md:hidden p-4 text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load analyses"}</div>}
        {!isLoading && !error && (
          <div className="md:hidden divide-y">
            {filtered.map((a) => (
              <div key={String(a.id)} className="p-4 space-y-2">
                <div className="flex items-center justify-between gap-2">
                  <p className="font-medium">{a.report_type ?? `Analysis #${a.id}`}</p>
                  <Badge variant="outline" className={statusStyles[(a.status ?? "").toLowerCase()] ?? ""}>{a.status ?? "ready"}</Badge>
                </div>
                <p className="text-xs text-muted-foreground">{a.date ?? a.reservation?.date ?? "—"}</p>
                <button
                  title="View analysis"
                  aria-label="View analysis"
                  className="inline-flex w-full h-9 items-center justify-center gap-2 rounded-md border hover:bg-muted text-sm"
                  onClick={() => setSelected(a)}
                >
                  <Eye className="h-4 w-4" />
                  View Details
                </button>
              </div>
            ))}
            {filtered.length === 0 && <div className="p-4 text-sm text-muted-foreground">No analyses found.</div>}
          </div>
        )}
      </div>

      <Dialog open={!!selected} onOpenChange={() => setSelected(null)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2"><FlaskConical className="h-5 w-5 text-primary" /> {selected?.report_type ?? `Analysis #${selected?.id}`}</DialogTitle>
          </DialogHeader>
          {selected && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div><span className="text-muted-foreground">Date:</span> <span className="font-medium">{selected.date ?? selected.reservation?.date ?? "—"}</span></div>
                <div><span className="text-muted-foreground">Status:</span> <span className="font-medium">{selected.status ?? "ready"}</span></div>
              </div>
              <div>
                <h4 className="font-semibold mb-2">Service Options</h4>
                <div className="border rounded-lg overflow-hidden">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Price</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {(selected.lab_service_options ?? []).map((t, i) => (
                        <TableRow key={i}>
                          <TableCell className="font-medium text-sm">{t.name ?? t.option_name ?? `Option ${t.id}`}</TableCell>
                          <TableCell className="text-sm">{t.price ?? "—"}</TableCell>
                        </TableRow>
                      ))}
                      {(selected.lab_service_options ?? []).length === 0 && (
                        <TableRow>
                          <TableCell colSpan={2} className="text-muted-foreground">No options found.</TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </div>
              </div>
              {selected.notes && (
                <div>
                  <h4 className="font-semibold mb-1 text-sm">Notes</h4>
                  <p className="text-sm text-muted-foreground">{selected.notes}</p>
                </div>
              )}
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
