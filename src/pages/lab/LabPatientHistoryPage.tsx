import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { labApi } from "@/lib/api";

type AnalysisHistory = {
  id: number | string;
  reservation_id?: number | string | null;
  date?: string | null;
  doctor_name?: string | null;
  payment?: string | null;
  cost?: string | number | null;
  report?: string | null;
  created_at?: string | null;
  services?: Array<{
    id: number | string;
    name?: string | null;
    value?: string | null;
    unit?: string | null;
    normal_range?: string | null;
    price?: string | number | null;
    images?: string[];
  }>;
};

export default function LabPatientHistoryPage() {
  const { id } = useParams<{ id: string }>();

  const historyQuery = useQuery({
    queryKey: ["lab", "patient-history", id],
    enabled: Boolean(id),
    queryFn: () => labApi.patientHistory(id as string),
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
      medical_analyses?: AnalysisHistory[];
    };
  }, [historyQuery.data]);

  const patient = root.patient ?? {};
  const analyses = Array.isArray(root.medical_analyses) ? root.medical_analyses : [];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Patient History</h2>
          <p className="text-sm text-muted-foreground">Patient details with previous medical analyses in this laboratory</p>
        </div>
        <Button asChild variant="outline" className="gap-2">
          <Link to="/lab-dashboard/patients">
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
            <h3 className="font-semibold">Previous Medical Analyses</h3>
            {analyses.length === 0 ? (
              <div className="rounded-lg border p-4 text-sm text-muted-foreground">No previous medical analyses found for this patient.</div>
            ) : (
              analyses.map((analysis) => (
                <div key={String(analysis.id)} className="rounded-lg border p-4 space-y-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">Analysis #{String(analysis.id)}</Badge>
                    <span className="text-sm text-muted-foreground">{analysis.date ?? "—"}</span>
                    {analysis.payment ? <Badge variant="secondary">{analysis.payment}</Badge> : null}
                    <span className="text-sm">Cost: {analysis.cost ?? "—"}</span>
                    <span className="text-sm">Doctor: {analysis.doctor_name ?? "—"}</span>
                    <span className="text-sm">Reservation: {analysis.reservation_id ?? "—"}</span>
                  </div>
                  <p className="text-sm"><span className="text-muted-foreground">Report:</span> {analysis.report ?? "—"}</p>
                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">Services</p>
                    {!analysis.services || analysis.services.length === 0 ? (
                      <p className="text-sm">No services attached.</p>
                    ) : (
                      <div className="space-y-1">
                        {analysis.services.map((service) => (
                          <div key={String(service.id)} className="text-sm rounded border p-2">
                            <span className="font-medium">{service.name ?? "Unnamed service"}</span>
                            <span> - Value: {service.value ?? "—"}</span>
                            <span> - Unit: {service.unit ?? "—"}</span>
                            <span> - Range: {service.normal_range ?? "—"}</span>
                            <span> - Price: {service.price ?? "—"}</span>
                          </div>
                        ))}
                      </div>
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
