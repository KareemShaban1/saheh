import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { FileText, Eye, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";

type Prescription = {
  id: number | string;
  title?: string | null;
  notes?: string | null;
  images?: string[];
  reservation?: {
    id?: number | string;
    date?: string | null;
    doctor_name?: string | null;
    clinic_name?: string | null;
  };
};

export default function PatientPrescriptions() {
  const { token } = useAuth();
  const [search, setSearch] = useState("");
  const [selected, setSelected] = useState<Prescription | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["patient", "prescriptions", token],
    queryFn: () => patientApi.prescriptions(token!),
    enabled: Boolean(token),
  });

  const prescriptions = useMemo<Prescription[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as Prescription[];
  }, [data]);

  const filtered = prescriptions.filter((p) =>
    `${p.title ?? ""} ${p.notes ?? ""} ${p.reservation?.doctor_name ?? ""} ${p.reservation?.clinic_name ?? ""}`
      .toLowerCase()
      .includes(search.toLowerCase()),
  );

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">Prescriptions</h2>
          <p className="text-muted-foreground text-sm mt-1">Dynamic data from your backend</p>
        </div>
        <div className="relative w-full sm:w-64">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-9" />
        </div>
      </div>

      <div className="bg-card rounded-xl border overflow-hidden">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Title</TableHead>
              <TableHead className="hidden md:table-cell">Doctor</TableHead>
              <TableHead className="hidden md:table-cell">Clinic</TableHead>
              <TableHead className="hidden sm:table-cell">Date</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {isLoading && (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground">Loading prescriptions...</TableCell>
              </TableRow>
            )}
            {error && (
              <TableRow>
                <TableCell colSpan={5} className="text-destructive">
                  {error instanceof Error ? error.message : "Failed to load prescriptions"}
                </TableCell>
              </TableRow>
            )}
            {!isLoading && !error && filtered.length === 0 && (
              <TableRow>
                <TableCell colSpan={5} className="text-muted-foreground">No prescriptions found.</TableCell>
              </TableRow>
            )}
            {filtered.map((p) => (
              <TableRow key={String(p.id)} className="border-b last:border-0">
                <TableCell className="font-medium">{p.title ?? `Prescription #${p.id}`}</TableCell>
                <TableCell className="hidden md:table-cell">{p.reservation?.doctor_name ?? "—"}</TableCell>
                <TableCell className="hidden md:table-cell">{p.reservation?.clinic_name ?? "—"}</TableCell>
                <TableCell className="hidden sm:table-cell text-sm">{p.reservation?.date ?? "—"}</TableCell>
                <TableCell>
                  <Button variant="ghost" size="icon" onClick={() => setSelected(p)}>
                    <Eye className="h-4 w-4" />
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      <Dialog open={!!selected} onOpenChange={() => setSelected(null)}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <FileText className="h-5 w-5 text-primary" />
              {selected?.title ?? `Prescription #${selected?.id}`}
            </DialogTitle>
          </DialogHeader>
          {selected && (
            <div className="space-y-3 text-sm">
              <p><span className="text-muted-foreground">Doctor:</span> {selected.reservation?.doctor_name ?? "—"}</p>
              <p><span className="text-muted-foreground">Clinic:</span> {selected.reservation?.clinic_name ?? "—"}</p>
              <p><span className="text-muted-foreground">Date:</span> {selected.reservation?.date ?? "—"}</p>
              <p><span className="text-muted-foreground">Images:</span> {(selected.images ?? []).length}</p>
              <p><span className="text-muted-foreground">Notes:</span> {selected.notes ?? "—"}</p>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
