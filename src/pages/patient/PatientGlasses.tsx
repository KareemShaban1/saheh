import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Glasses } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";

type GlassesPrescription = {
  id: number | string;
  SPH_R_D?: string | null;
  CYL_R_D?: string | null;
  AX_R_D?: string | null;
  SPH_L_D?: string | null;
  CYL_L_D?: string | null;
  AX_L_D?: string | null;
  SPH_R_N?: string | null;
  CYL_R_N?: string | null;
  AX_R_N?: string | null;
  SPH_L_N?: string | null;
  CYL_L_N?: string | null;
  AX_L_N?: string | null;
  reservation?: {
    id?: number | string;
    date?: string | null;
  };
};

export default function PatientGlasses() {
  const { token } = useAuth();

  const { data, isLoading, error } = useQuery({
    queryKey: ["patient", "glasses-distance", token],
    queryFn: () => patientApi.glassesDistance(token!),
    enabled: Boolean(token),
  });

  const rows = useMemo<GlassesPrescription[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as GlassesPrescription[];
  }, [data]);

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">Glasses Prescription</h2>
          <p className="text-muted-foreground text-sm mt-1">Dynamic data from your backend</p>
        </div>
      </div>

      {isLoading && <div className="text-sm text-muted-foreground">Loading glasses records...</div>}
      {error && <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load glasses records"}</div>}

      {!isLoading && !error && (
        <div className="space-y-4">
          {rows.length === 0 && (
            <div className="rounded-xl border bg-card p-4 text-sm text-muted-foreground">No glasses records found.</div>
          )}
          {rows.map((p) => (
            <div key={String(p.id)} className="bg-card rounded-xl border p-5">
              <div className="flex items-start gap-3 mb-4">
                <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                  <Glasses className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <h3 className="font-semibold">Record #{String(p.id)}</h3>
                  <p className="text-xs text-muted-foreground">Date: {p.reservation?.date ?? "—"}</p>
                </div>
              </div>

              <div className="border rounded-lg overflow-hidden">
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-20">Eye</TableHead>
                        <TableHead>SPH</TableHead>
                        <TableHead>CYL</TableHead>
                        <TableHead>Axis</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow>
                        <TableCell className="font-medium text-sm">Distance R</TableCell>
                        <TableCell className="text-sm">{p.SPH_R_D ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.CYL_R_D ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.AX_R_D ?? "—"}</TableCell>
                      </TableRow>
                      <TableRow>
                        <TableCell className="font-medium text-sm">Distance L</TableCell>
                        <TableCell className="text-sm">{p.SPH_L_D ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.CYL_L_D ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.AX_L_D ?? "—"}</TableCell>
                      </TableRow>
                      <TableRow>
                        <TableCell className="font-medium text-sm">Near R</TableCell>
                        <TableCell className="text-sm">{p.SPH_R_N ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.CYL_R_N ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.AX_R_N ?? "—"}</TableCell>
                      </TableRow>
                      <TableRow>
                        <TableCell className="font-medium text-sm">Near L</TableCell>
                        <TableCell className="text-sm">{p.SPH_L_N ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.CYL_L_N ?? "—"}</TableCell>
                        <TableCell className="text-sm">{p.AX_L_N ?? "—"}</TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
