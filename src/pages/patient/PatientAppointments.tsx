import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CalendarDays, Clock, User, Plus, Eye, ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

const statusStyles: Record<string, string> = {
  confirmed: "bg-success/10 text-success border-success/20",
  pending: "bg-warning/10 text-warning border-warning/20",
  completed: "bg-muted text-muted-foreground",
  cancelled: "bg-destructive/10 text-destructive border-destructive/20",
};

const asRecord = (value: unknown): Record<string, unknown> | null =>
  value && typeof value === "object" ? (value as Record<string, unknown>) : null;

export default function PatientAppointments() {
  const { token } = useAuth();
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isDetailsOpen, setIsDetailsOpen] = useState(false);
  const [selectedReservationId, setSelectedReservationId] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [form, setForm] = useState({
    clinic_id: "",
    doctor_id: "",
    date: "",
    reservation_value: "",
  });
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["patient", "reservations", token],
    queryFn: () => patientApi.reservations(token!),
    enabled: !!token,
  });

  const clinicsQuery = useQuery({
    queryKey: ["patient", "clinics", token],
    queryFn: () => patientApi.clinics(token!),
    enabled: !!token,
  });

  const doctorsQuery = useQuery({
    queryKey: ["patient", "doctors", token, form.clinic_id],
    queryFn: () => patientApi.doctors(token!, { clinic_id: form.clinic_id }),
    enabled: !!token && !!form.clinic_id,
  });

  const optionsQuery = useQuery({
    queryKey: ["patient", "reservation-options", token, form.clinic_id, form.doctor_id, form.date],
    queryFn: () =>
      patientApi.doctorReservationOptions(token!, {
        clinic_id: form.clinic_id,
        doctor_id: form.doctor_id,
        reservation_date: form.date,
      }),
    enabled: !!token && !!form.clinic_id && !!form.doctor_id && !!form.date,
  });

  const detailsQuery = useQuery({
    queryKey: ["patient", "reservation", token, selectedReservationId],
    queryFn: () => patientApi.reservation(token!, selectedReservationId!),
    enabled: !!token && !!selectedReservationId && isDetailsOpen,
  });

  const createMutation = useMutation({
    mutationFn: (payload: Record<string, unknown>) => patientApi.storeReservation(token!, payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["patient", "reservations"] });
      toast({ title: "Reservation created successfully" });
      setIsCreateOpen(false);
      setForm({ clinic_id: "", doctor_id: "", date: "", reservation_value: "" });
    },
    onError: (e) =>
      toast({
        title: "Failed to create reservation",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const raw = (data as { data?: unknown })?.data ?? data;
  const reservations = Array.isArray(raw) ? raw : (raw as { data?: unknown[] })?.data ?? [];
  const totalPages = Math.max(1, Math.ceil(reservations.length / perPage));
  const safePage = Math.min(page, totalPages);
  const pagedReservations = reservations.slice((safePage - 1) * perPage, safePage * perPage);
  const clinicsRaw = (clinicsQuery.data as { data?: unknown })?.data ?? clinicsQuery.data;
  const clinics = (Array.isArray(clinicsRaw) ? clinicsRaw : (clinicsRaw as { data?: unknown[] })?.data ?? []) as Array<Record<string, unknown>>;
  const doctorsRaw = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
  const doctors = (Array.isArray(doctorsRaw) ? doctorsRaw : (doctorsRaw as { data?: unknown[] })?.data ?? []) as Array<Record<string, unknown>>;
  const optionsRaw = (optionsQuery.data as { data?: unknown })?.data ?? optionsQuery.data;
  const options = useMemo<Record<string, unknown>>(
    () => (optionsRaw && typeof optionsRaw === "object" ? (optionsRaw as Record<string, unknown>) : {}),
    [optionsRaw],
  );
  const optionType = String(options.type ?? "");
  const availableValues = useMemo<string[]>(() => {
    if (optionType === "numbers") {
      return ((options.available_numbers as Array<string | number> | undefined) ?? []).map((v) => String(v));
    }
    return ((options.available_slots as Array<string | number> | undefined) ?? []).map((v) => String(v));
  }, [options, optionType]);

  const detailsRaw = (detailsQuery.data as { data?: unknown })?.data ?? detailsQuery.data;
  const reservationDetails = (detailsRaw as Record<string, unknown>) ?? null;

  const openDetails = (id: string | number) => {
    setSelectedReservationId(String(id));
    setIsDetailsOpen(true);
  };

  const onCreateReservation = () => {
    if (!form.clinic_id || !form.doctor_id || !form.date || !form.reservation_value) {
      toast({ title: "Please complete all required fields", variant: "destructive" });
      return;
    }

    const payload: Record<string, unknown> = {
      clinic_id: Number(form.clinic_id),
      doctor_id: Number(form.doctor_id),
      date: form.date,
    };

    if (optionType === "numbers") {
      payload.reservation_number = form.reservation_value;
    } else {
      payload.slot = form.reservation_value;
    }

    createMutation.mutate(payload);
  };

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">My Appointments</h2>
          <p className="text-muted-foreground text-sm mt-1">Manage your reservations</p>
        </div>
        <div className="flex gap-2">
          <Button size="sm" className="gap-2 gradient-primary text-primary-foreground border-0" onClick={() => setIsCreateOpen(true)}>
            <Plus className="h-4 w-4" /> New Booking
          </Button>
        </div>
      </div>

      {isLoading && (
        <div className="text-muted-foreground py-8">Loading reservations…</div>
      )}
      {error && (
        <div className="rounded-lg bg-destructive/10 text-destructive p-4">
          {error instanceof Error ? error.message : "Failed to load reservations"}
        </div>
      )}
      {!isLoading && !error && reservations.length === 0 && (
        <div className="bg-card rounded-xl border p-8 text-center text-muted-foreground">
          No reservations yet. Book an appointment from a clinic page.
        </div>
      )}
      {!isLoading && !error && reservations.length > 0 && (
        <div className="bg-card rounded-xl border shadow-card overflow-hidden">
          <div className="hidden md:block overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/50">
                  <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Doctor</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Clinic</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Date</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Number</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Solt</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {pagedReservations.map((apt: Record<string, unknown>, i: number) => (
                  <tr key={String(apt.id ?? i)} className="hover:bg-muted/30 transition-colors">
                    <td className="p-4 text-muted-foreground">{String(apt.id ?? (i + 1 + (safePage - 1) * perPage))}</td>
                    <td className="p-4">
                      <div className="flex items-center gap-2">
                        <div className="h-8 w-8 rounded-full bg-sidebar-accent flex items-center justify-center shrink-0">
                          <User className="h-4 w-4 text-primary" />
                        </div>
                        <span className="font-medium">
                          {String(apt.doctor_name ?? asRecord(asRecord(apt.doctor)?.user)?.name ?? "—")}
                        </span>
                      </div>
                    </td>
                    <td className="p-4 text-muted-foreground">{String(apt.clinic_name ?? asRecord(apt.clinic)?.name ?? "—")}</td>
                    <td className="p-4 text-muted-foreground">
                      <span className="inline-flex items-center gap-1">
                        <CalendarDays className="h-3.5 w-3.5" /> {String(apt.date ?? apt.reservation_date ?? "—")}
                      </span>
                    </td>
                    <td className="p-4 text-muted-foreground">
                      <span className="inline-flex items-center gap-1">
                         {String(apt.reservation_number ?? "—")}
                      </span>
                    </td>
 		<td className="p-4 text-muted-foreground">
                      <span className="inline-flex items-center gap-1">
                        <Clock className="h-3.5 w-3.5" /> {String(apt.slot ?? "—")}
                      </span>
                    </td>
                    <td className="p-4">
                      <Badge variant="outline" className={statusStyles[String(apt.status ?? "pending")] ?? ""}>
                        {String(apt.status ?? "pending")}
                      </Badge>
                    </td>
                    <td className="p-4">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openDetails(String(apt.id ?? ""))}>
                        <Eye className="h-4 w-4" /> Details
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="md:hidden divide-y">
            {pagedReservations.map((apt: Record<string, unknown>, i: number) => (
              <div key={String(apt.id ?? i)} className="p-4 space-y-2">
                <div className="flex items-center justify-between gap-2">
                  <p className="font-medium">{String(apt.doctor_name ?? asRecord(asRecord(apt.doctor)?.user)?.name ?? "—")}</p>
                  <Badge variant="outline" className={statusStyles[String(apt.status ?? "pending")] ?? ""}>
                    {String(apt.status ?? "pending")}
                  </Badge>
                </div>
                <p className="text-sm text-muted-foreground">{String(apt.clinic_name ?? asRecord(apt.clinic)?.name ?? "—")}</p>
                <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                  <span className="inline-flex items-center gap-1">
                    <CalendarDays className="h-3.5 w-3.5" /> {String(apt.date ?? apt.reservation_date ?? "—")}
                  </span>
                  <span>#{String(apt.reservation_number ?? "—")}</span>
                  <span className="inline-flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5" /> {String(apt.slot ?? "—")}
                  </span>
                </div>
                <Button variant="outline" size="sm" className="gap-2 w-full" onClick={() => openDetails(String(apt.id ?? ""))}>
                  <Eye className="h-4 w-4" /> Details
                </Button>
              </div>
            ))}
          </div>
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">
              Showing {(safePage - 1) * perPage + 1}-{Math.min(safePage * perPage, reservations.length)} of {reservations.length}
            </p>
            <div className="flex gap-2">
              <Button variant="outline" size="icon" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <Button variant="outline" size="icon" onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={safePage >= totalPages}>
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      )}

      <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Make Reservation</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="clinic">Clinic</Label>
              <select
                id="clinic"
                title="Clinic"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.clinic_id}
                onChange={(e) => setForm({ clinic_id: e.target.value, doctor_id: "", date: "", reservation_value: "" })}
              >
                <option value="">Select clinic</option>
                {clinics.map((clinic) => (
                  <option key={String(clinic.id)} value={String(clinic.id)}>
                    {String(clinic.name ?? `Clinic ${clinic.id}`)}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="doctor">Doctor</Label>
              <select
                id="doctor"
                title="Doctor"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.doctor_id}
                onChange={(e) => setForm((prev) => ({ ...prev, doctor_id: e.target.value, reservation_value: "" }))}
                disabled={!form.clinic_id}
              >
                <option value="">Select doctor</option>
                {doctors.map((doctor) => (
                  <option key={String(doctor.id)} value={String(doctor.id)}>
                    {String(doctor.name ?? doctor.doctor_name ?? `Doctor ${doctor.id}`)}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="date">Date</Label>
              <Input
                id="date"
                type="date"
                value={form.date}
                onChange={(e) => setForm((prev) => ({ ...prev, date: e.target.value, reservation_value: "" }))}
                min={new Date().toISOString().slice(0, 10)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="value">{optionType === "numbers" ? "Reservation Number" : "Reservation Slot"}</Label>
              <select
                id="value"
                title="Reservation value"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.reservation_value}
                onChange={(e) => setForm((prev) => ({ ...prev, reservation_value: e.target.value }))}
                disabled={!form.clinic_id || !form.doctor_id || !form.date || optionsQuery.isLoading}
              >
                <option value="">{optionsQuery.isLoading ? "Loading..." : "Select available option"}</option>
                {availableValues.map((value) => (
                  <option key={value} value={value}>
                    {value}
                  </option>
                ))}
              </select>
              {form.clinic_id && form.doctor_id && form.date && !optionsQuery.isLoading && availableValues.length === 0 && (
                <p className="text-xs text-muted-foreground">No available options for this date.</p>
              )}
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsCreateOpen(false)}>Cancel</Button>
            <Button onClick={onCreateReservation} disabled={createMutation.isPending}>
              {createMutation.isPending ? "Saving..." : "Confirm"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Reservation Details</DialogTitle>
          </DialogHeader>
          {detailsQuery.isLoading && <div className="text-sm text-muted-foreground">Loading details...</div>}
          {detailsQuery.error && <div className="text-sm text-destructive">{detailsQuery.error instanceof Error ? detailsQuery.error.message : "Failed to load details"}</div>}
          {!detailsQuery.isLoading && !detailsQuery.error && reservationDetails && (
            <div className="space-y-3 text-sm">
              <div className="grid sm:grid-cols-2 gap-3">
                <p><span className="text-muted-foreground">Reservation #:</span> {String(reservationDetails.reservation_number ?? reservationDetails.id ?? "—")}</p>
                <p><span className="text-muted-foreground">Date:</span> {String(reservationDetails.date ?? "—")}</p>
                <p><span className="text-muted-foreground">Payment:</span> {String(reservationDetails.payment ?? "—")}</p>
                <p><span className="text-muted-foreground">Cost:</span> {String(reservationDetails.cost ?? "—")}</p>
                <p><span className="text-muted-foreground">Status:</span> {String(reservationDetails.reservation_status ?? reservationDetails.status ?? "—")}</p>
                <p><span className="text-muted-foreground">Acceptance:</span> {String(reservationDetails.acceptance ?? "—")}</p>
                <p><span className="text-muted-foreground">First Diagnosis:</span> {String(reservationDetails.first_diagnosis ?? "—")}</p>
                <p><span className="text-muted-foreground">Final Diagnosis:</span> {String(reservationDetails.final_diagnosis ?? "—")}</p>
              </div>
              <div>
                <p className="font-medium mb-1">Services</p>
                <div className="space-y-1">
                  {((reservationDetails.services as Array<Record<string, unknown>> | undefined) ?? []).map((service, idx) => (
                    <p key={idx} className="text-muted-foreground">
                      {String(service.service_name ?? `Service ${idx + 1}`)} - {String(service.fee ?? service.price ?? "0")}
                    </p>
                  ))}
                  {(((reservationDetails.services as Array<Record<string, unknown>> | undefined) ?? []).length === 0) && (
                    <p className="text-muted-foreground">No services attached.</p>
                  )}
                </div>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
