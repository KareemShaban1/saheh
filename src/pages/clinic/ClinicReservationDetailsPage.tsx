import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link, useParams, useSearchParams } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { clinicApi } from "@/lib/api";

type ReservationDetails = {
  id: number | string;
  patient_id?: number;
  patient_name?: string;
  doctor_name?: string;
  date?: string;
  time?: string;
  slot?: string | null;
  reservation_number?: string | null;
  status?: string;
  acceptance?: string;
  payment?: string;
  month?: string;
};

export default function ClinicReservationDetailsPage() {
  const { id } = useParams<{ id: string }>();
  const [searchParams] = useSearchParams();
  const patientId = searchParams.get("patient_id");

  const reservationQuery = useQuery({
    queryKey: ["clinic", "reservation", id, "details-page"],
    enabled: Boolean(id),
    queryFn: () => clinicApi.reservation(id as string),
  });

  const prescriptionQuery = useQuery({
    queryKey: ["clinic", "reservation", id, "prescription"],
    enabled: Boolean(id),
    queryFn: () => clinicApi.reservationPrescription(id as string),
  });

  const glassesQuery = useQuery({
    queryKey: ["clinic", "reservation", id, "glasses"],
    enabled: Boolean(id),
    queryFn: () => clinicApi.reservationGlassesDistances(id as string),
  });

  const teethQuery = useQuery({
    queryKey: ["clinic", "reservation", id, "teeth"],
    enabled: Boolean(id),
    queryFn: () => clinicApi.reservationTeeth(id as string),
  });

  const reservation = useMemo(() => {
    const root = (reservationQuery.data as { data?: unknown })?.data ?? reservationQuery.data;
    return ((root && typeof root === "object" ? root : {}) as ReservationDetails);
  }, [reservationQuery.data]);

  const prescription = useMemo(() => {
    const root = (prescriptionQuery.data as { data?: unknown })?.data ?? {};
    return (root && typeof root === "object" ? root : {}) as {
      title?: string | null;
      notes?: string | null;
      images?: string[];
      drugs?: Array<{
        id?: number | string;
        name?: string;
        type?: string;
        dose?: string;
        frequency?: string;
        period?: string;
        notes?: string | null;
      }>;
    };
  }, [prescriptionQuery.data]);

  const glassesRows = useMemo(() => {
    const root = (glassesQuery.data as { data?: unknown })?.data ?? glassesQuery.data;
    return Array.isArray(root) ? root : [];
  }, [glassesQuery.data]) as Array<{
    id: number | string;
    created_at?: string | null;
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
  }>;

  const teethData = useMemo(() => {
    const root = (teethQuery.data as { data?: unknown })?.data ?? {};
    return (root && typeof root === "object" ? root : {}) as {
      general_note?: string | null;
      next_session_plan?: string | null;
      teeth?: Array<{ id?: number | string; tooth_number: number; tooth_note?: string | null }>;
    };
  }, [teethQuery.data]);

  const loading = reservationQuery.isLoading || prescriptionQuery.isLoading || glassesQuery.isLoading || teethQuery.isLoading;
  const hasError = reservationQuery.error || prescriptionQuery.error || glassesQuery.error || teethQuery.error;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Reservation Details</h2>
          <p className="text-sm text-muted-foreground">Modules and data related to reservation #{id}</p>
        </div>
        <Button asChild variant="outline" className="gap-2">
          <Link to={patientId ? `/clinic-dashboard/patients/${patientId}/history` : "/clinic-dashboard/reservations"}>
            <ArrowLeft className="h-4 w-4" />
            Back
          </Link>
        </Button>
      </div>

      {loading ? <div className="rounded-lg border p-4 text-sm text-muted-foreground">Loading reservation modules...</div> : null}
      {hasError ? (
        <div className="rounded-lg border p-4 text-sm text-destructive">
          {hasError instanceof Error ? hasError.message : "Failed to load reservation details"}
        </div>
      ) : null}

      {!loading && !hasError ? (
        <Tabs defaultValue="details" className="space-y-4">
          <TabsList className="flex flex-wrap h-auto">
            <TabsTrigger value="details">Reservation Details</TabsTrigger>
            <TabsTrigger value="prescription">Prescription</TabsTrigger>
            <TabsTrigger value="glasses">Glasses Distances</TabsTrigger>
            <TabsTrigger value="teeth">Teeth</TabsTrigger>
          </TabsList>

          <TabsContent value="details" className="rounded-lg border p-4">
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
              <div><span className="text-muted-foreground">Patient:</span> {reservation.patient_name ?? "—"}</div>
              <div><span className="text-muted-foreground">Doctor:</span> {reservation.doctor_name ?? "—"}</div>
              <div><span className="text-muted-foreground">Date:</span> {reservation.date ?? "—"}</div>
              <div><span className="text-muted-foreground">Time/Slot:</span> {reservation.time ?? reservation.slot ?? reservation.reservation_number ?? "—"}</div>
              <div><span className="text-muted-foreground">Status:</span> {reservation.status ?? "—"}</div>
              <div><span className="text-muted-foreground">Acceptance:</span> {reservation.acceptance ?? "—"}</div>
              <div><span className="text-muted-foreground">Payment:</span> {reservation.payment ?? "—"}</div>
              <div><span className="text-muted-foreground">Month:</span> {reservation.month ?? "—"}</div>
            </div>
          </TabsContent>

          <TabsContent value="prescription" className="rounded-lg border p-4 space-y-3">
            <div className="text-sm"><span className="text-muted-foreground">Title:</span> {prescription.title ?? "—"}</div>
            <div className="text-sm"><span className="text-muted-foreground">Notes:</span> {prescription.notes ?? "—"}</div>
            <div className="space-y-2">
              <p className="text-sm font-medium">Drugs</p>
              {(prescription.drugs ?? []).length === 0 ? (
                <p className="text-sm text-muted-foreground">No drugs.</p>
              ) : (
                <div className="space-y-2">
                  {(prescription.drugs ?? []).map((drug) => (
                    <div key={String(drug.id ?? `${drug.name}-${drug.type}`)} className="rounded-md border p-2 text-sm">
                      <p><span className="text-muted-foreground">Name:</span> {drug.name ?? "—"} | <span className="text-muted-foreground">Type:</span> {drug.type ?? "—"}</p>
                      <p><span className="text-muted-foreground">Dose:</span> {drug.dose ?? "—"} | <span className="text-muted-foreground">Frequency:</span> {drug.frequency ?? "—"} | <span className="text-muted-foreground">Period:</span> {drug.period ?? "—"}</p>
                      <p><span className="text-muted-foreground">Notes:</span> {drug.notes ?? "—"}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>
            {(prescription.images ?? []).length > 0 ? (
              <div className="space-y-2">
                <p className="text-sm font-medium">Images</p>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                  {(prescription.images ?? []).map((url) => (
                    <img key={url} src={url} alt="Prescription" className="h-28 w-full object-cover rounded border" />
                  ))}
                </div>
              </div>
            ) : null}
          </TabsContent>

          <TabsContent value="glasses" className="rounded-lg border p-4 space-y-2">
            {glassesRows.length === 0 ? (
              <p className="text-sm text-muted-foreground">No glasses distances records.</p>
            ) : (
              glassesRows.map((row) => (
                <div key={String(row.id)} className="rounded-md border p-2 text-sm">
                  <p className="text-muted-foreground text-xs">{row.created_at ?? "—"}</p>
                  <p>D: R({row.SPH_R_D ?? "—"}/{row.CYL_R_D ?? "—"}/{row.AX_R_D ?? "—"}) L({row.SPH_L_D ?? "—"}/{row.CYL_L_D ?? "—"}/{row.AX_L_D ?? "—"})</p>
                  <p>N: R({row.SPH_R_N ?? "—"}/{row.CYL_R_N ?? "—"}/{row.AX_R_N ?? "—"}) L({row.SPH_L_N ?? "—"}/{row.CYL_L_N ?? "—"}/{row.AX_L_N ?? "—"})</p>
                </div>
              ))
            )}
          </TabsContent>

          <TabsContent value="teeth" className="rounded-lg border p-4 space-y-2">
            {(teethData.teeth ?? []).length === 0 ? (
              <p className="text-sm text-muted-foreground">No teeth records.</p>
            ) : (
              <>
                <p className="text-sm"><span className="text-muted-foreground">General Note:</span> {teethData.general_note ?? "—"}</p>
                <p className="text-sm"><span className="text-muted-foreground">Next Session Plan:</span> {teethData.next_session_plan ?? "—"}</p>
                <div className="flex flex-wrap gap-2">
                  {(teethData.teeth ?? []).map((tooth) => (
                    <Badge key={String(tooth.id ?? tooth.tooth_number)} variant="outline">
                      #{tooth.tooth_number}{tooth.tooth_note ? ` - ${tooth.tooth_note}` : ""}
                    </Badge>
                  ))}
                </div>
              </>
            )}
          </TabsContent>
        </Tabs>
      ) : null}
    </div>
  );
}
