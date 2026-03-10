import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft, Eye } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { clinicApi } from "@/lib/api";

type ReservationHistory = {
  id: number | string;
  date?: string | null;
  time?: string | null;
  slot?: string | null;
  reservation_number?: string | null;
  status?: string | null;
  acceptance?: string | null;
  payment?: string | null;
  doctor_name?: string | null;
};

export default function ClinicPatientHistoryPage() {
  const { id } = useParams<{ id: string }>();

  const historyQuery = useQuery({
    queryKey: ["clinic", "patient-history", id],
    enabled: Boolean(id),
    queryFn: () => clinicApi.patientHistory(id as string),
  });

  const root = useMemo(() => {
    const raw = (historyQuery.data as { data?: unknown })?.data ?? historyQuery.data;
    return (raw && typeof raw === "object" ? raw : {}) as {
      patient?: {
        id?: number | string;
        name?: string;
        phone?: string;
        email?: string;
        address?: string;
        age?: string;
        gender?: string;
        blood_group?: string;
      };
      reservations?: ReservationHistory[];
      patient_level_glasses_distances?: Array<{
        id: number | string;
        created_at?: string | null;
      }>;
      patient_level_tooth_records?: Array<{
        id: number | string;
        tooth_number: number;
        status?: string | null;
        notes?: string | null;
      }>;
    };
  }, [historyQuery.data]);

  const reservations = Array.isArray(root.reservations) ? root.reservations : [];
  const patient = root.patient ?? {};

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Patient History / Profile</h2>
          <p className="text-sm text-muted-foreground">All data related to this patient in current clinic</p>
        </div>
        <Button asChild variant="outline" className="gap-2">
          <Link to="/clinic-dashboard/patients">
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
              <div><span className="text-muted-foreground">Phone:</span> {patient.phone ?? "—"}</div>
              <div><span className="text-muted-foreground">Email:</span> {patient.email ?? "—"}</div>
              <div><span className="text-muted-foreground">Gender:</span> {patient.gender ?? "—"}</div>
              <div><span className="text-muted-foreground">Age:</span> {patient.age ?? "—"}</div>
              <div><span className="text-muted-foreground">Blood Group:</span> {patient.blood_group ?? "—"}</div>
              <div className="sm:col-span-2 lg:col-span-2"><span className="text-muted-foreground">Address:</span> {patient.address ?? "—"}</div>
            </div>
          </div>

          <div className="space-y-3">
            <h3 className="font-semibold">Reservations</h3>
            {reservations.length === 0 ? (
              <div className="rounded-lg border p-4 text-sm text-muted-foreground">No reservations found for this patient in current clinic.</div>
            ) : (
              reservations.map((reservation) => (
                <div key={String(reservation.id)} className="rounded-lg border p-4">
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">Reservation #{String(reservation.id)}</Badge>
                    <span className="text-sm text-muted-foreground">
                      {reservation.date ?? "—"} {reservation.time ?? reservation.slot ?? reservation.reservation_number ?? ""}
                    </span>
                    {reservation.status ? <Badge variant="secondary">{reservation.status}</Badge> : null}
                    {reservation.acceptance ? <Badge variant="outline">Acceptance: {reservation.acceptance}</Badge> : null}
                    {reservation.payment ? <Badge variant="outline">Payment: {reservation.payment}</Badge> : null}
                    <span className="text-sm">Doctor: {reservation.doctor_name ?? "—"}</span>
                    <Button asChild size="sm" variant="outline" className="ms-auto gap-2">
                      <Link to={`/clinic-dashboard/reservations/${reservation.id}?patient_id=${id}`}>
                        <Eye className="h-4 w-4" />
                        Open Details
                      </Link>
                    </Button>
                  </div>
                </div>
              ))
            )}
          </div>

          <div className="rounded-lg border p-4 space-y-2">
            <h3 className="font-semibold">Patient-Level Modules (Outside Reservation)</h3>
            <p className="text-sm">Glasses records: {(root.patient_level_glasses_distances ?? []).length}</p>
            <p className="text-sm">Legacy tooth records: {(root.patient_level_tooth_records ?? []).length}</p>
          </div>
        </>
      ) : null}
    </div>
  );
}
