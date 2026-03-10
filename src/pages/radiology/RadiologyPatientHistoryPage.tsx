import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { radiologyApi } from "@/lib/api";

type RayHistory = {
  id: number | string;
  reservation_id?: number | string | null;
  date?: string | null;
  payment?: string | null;
  cost?: string | number | null;
  report?: string | null;
  images?: string[];
  created_at?: string | null;
};

export default function RadiologyPatientHistoryPage() {
  const { id } = useParams<{ id: string }>();

  const historyQuery = useQuery({
    queryKey: ["radiology", "patient-history", id],
    enabled: Boolean(id),
    queryFn: () => radiologyApi.patientHistory(id as string),
  });

  const root = useMemo(() => {
    const raw = (historyQuery.data as { data?: unknown })?.data ?? historyQuery.data;
    return (raw && typeof raw === "object" ? raw : {}) as {
      patient?: {
        id?: number | string;
        patient_code?: string;
        name?: string;
        phone?: string;
        email?: string;
        address?: string;
        age?: string;
        gender?: string;
        blood_group?: string;
      };
      rays?: RayHistory[];
    };
  }, [historyQuery.data]);

  const patient = root.patient ?? {};
  const rays = Array.isArray(root.rays) ? root.rays : [];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Patient History</h2>
          <p className="text-sm text-muted-foreground">Patient details with all previous rays in this radiology center</p>
        </div>
        <Button asChild variant="outline" className="gap-2">
          <Link to="/radiology-dashboard/patients">
            <ArrowLeft className="h-4 w-4" />
            Back to Patients
          </Link>
        </Button>
      </div>

      {historyQuery.isLoading ? (
        <div className="rounded-lg border p-4 text-sm text-muted-foreground">Loading patient history...</div>
      ) : null}
      {historyQuery.error ? (
        <div className="rounded-lg border p-4 text-sm text-destructive">
          {historyQuery.error instanceof Error ? historyQuery.error.message : "Failed to load patient history"}
        </div>
      ) : null}

      {!historyQuery.isLoading && !historyQuery.error ? (
        <>
          <div className="rounded-lg border p-4">
            <h3 className="font-semibold mb-2">Patient Info</h3>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
              <div><span className="text-muted-foreground">Name:</span> {patient.name ?? "—"}</div>
              <div><span className="text-muted-foreground">Code:</span> {patient.patient_code ?? "—"}</div>
              <div><span className="text-muted-foreground">Phone:</span> {patient.phone ?? "—"}</div>
              <div><span className="text-muted-foreground">Email:</span> {patient.email ?? "—"}</div>
              <div><span className="text-muted-foreground">Gender:</span> {patient.gender ?? "—"}</div>
              <div><span className="text-muted-foreground">Age:</span> {patient.age ?? "—"}</div>
              <div><span className="text-muted-foreground">Blood Group:</span> {patient.blood_group ?? "—"}</div>
              <div className="sm:col-span-2 lg:col-span-2"><span className="text-muted-foreground">Address:</span> {patient.address ?? "—"}</div>
            </div>
          </div>

          <div className="space-y-3">
            <h3 className="font-semibold">Previous Rays</h3>
            {rays.length === 0 ? (
              <div className="rounded-lg border p-4 text-sm text-muted-foreground">No previous rays found for this patient.</div>
            ) : (
              rays.map((ray) => (
                <div key={String(ray.id)} className="rounded-lg border p-4 space-y-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">Ray #{String(ray.id)}</Badge>
                    <span className="text-sm text-muted-foreground">{ray.date ?? "—"}</span>
                    {ray.payment ? <Badge variant="secondary">{ray.payment}</Badge> : null}
                    <span className="text-sm">Cost: {ray.cost ?? "—"}</span>
                    <span className="text-sm">Reservation: {ray.reservation_id ?? "—"}</span>
                  </div>
                  <p className="text-sm"><span className="text-muted-foreground">Report:</span> {ray.report ?? "—"}</p>
                  <div className="text-sm">
                    <span className="text-muted-foreground">Images:</span>{" "}
                    {Array.isArray(ray.images) && ray.images.length > 0 ? (
                      <span>{ray.images.length} attached</span>
                    ) : (
                      <span>None</span>
                    )}
                  </div>
                </div>
              ))
            )}
          </div>
        </>
      ) : null}
    </div>
  );
}
