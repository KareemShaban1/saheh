import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { CalendarDays, Check, ChevronLeft, ChevronRight, ChevronsUpDown, Edit, Glasses, Plus, Search, Smile, Trash2 } from "lucide-react";
import { Link, useNavigate } from "react-router-dom";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem } from "@/components/ui/command";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";
import { cn } from "@/lib/utils";

type ReservationRow = {
  id: number | string;
  parent_id?: number | null;
  type?: "reservation" | "session" | string;
  patient_id?: number;
  doctor_id?: number;
  patient_name?: string;
  doctor_name?: string;
  date?: string;
  time?: string;
  reservation_number?: string | null;
  slot?: string | null;
  status?: string;
  acceptance?: string;
  payment?: string;
  remaining?: number;
  paid_amount?: number;
  cost?: number | string | null;
  month?: string;
};

type SessionPaymentRow = {
  date: string;
  amount: string;
  payment_way: "cash";
};

type SessionContextPayload = {
  reservation?: {
    id?: number | string;
    patient_name?: string;
    doctor_name?: string;
    date?: string;
    reservation_number?: string | null;
    slot?: string | null;
    cost?: number;
    payment?: string;
  };
  previous_sessions?: Array<{
    id: number | string;
    date?: string;
    reservation_number?: string | null;
    slot?: string | null;
    status?: string;
    payment?: string;
    remaining?: number;
    paid_amount?: number;
  }>;
  payment_history?: Array<{
    id: number | string;
    module_id?: number | string;
    module_type?: string;
    date?: string;
    amount?: number;
    remaining?: number;
    payment_way?: string | null;
  }>;
  remaining?: number;
  paid_amount?: number;
  payment?: string;
  can_add_payment?: boolean;
};

type DrugRow = {
  selected_drug_id?: string;
  name: string;
  type: string;
  dose: string;
  frequency: string;
  period: string;
  notes: string;
};

type ClinicDrugOption = {
  id: number | string;
  name?: string;
  type?: string;
  dose?: string;
  frequency?: string;
  period?: string;
  notes?: string | null;
};

type ReservationRayRow = {
  id: number | string;
  reservation_id?: number | string | null;
  patient_id?: number | string | null;
  date?: string | null;
  payment?: "paid" | "not_paid";
  report?: string | null;
  cost?: string | null;
  images?: string[];
  created_at?: string | null;
};

type GlassesDistanceForm = {
  SPH_R_D: string;
  CYL_R_D: string;
  AX_R_D: string;
  SPH_L_D: string;
  CYL_L_D: string;
  AX_L_D: string;
  SPH_R_N: string;
  CYL_R_N: string;
  AX_R_N: string;
  SPH_L_N: string;
  CYL_L_N: string;
  AX_L_N: string;
};

const emptyGlassesForm = (): GlassesDistanceForm => ({
  SPH_R_D: "",
  CYL_R_D: "",
  AX_R_D: "",
  SPH_L_D: "",
  CYL_L_D: "",
  AX_L_D: "",
  SPH_R_N: "",
  CYL_R_N: "",
  AX_R_N: "",
  SPH_L_N: "",
  CYL_L_N: "",
  AX_L_N: "",
});

type ReservationToothRow = {
  id?: number | string;
  tooth_number: number;
  tooth_note?: string | null;
};

const UPPER_TEETH = Array.from({ length: 16 }, (_, i) => i + 1);
const LOWER_TEETH = Array.from({ length: 16 }, (_, i) => i + 17);

function ToothSvg({ selected }: { selected: boolean }) {
  return (
    <svg viewBox="0 0 64 64" className={`h-9 w-9 ${selected ? "text-primary" : "text-muted-foreground"}`} aria-hidden="true">
      <path
        d="M20 7c-7 0-12 5-12 12 0 5 2 9 4 13 2 4 2 8 3 13 1 7 5 12 10 12 5 0 6-6 7-11 1-3 1-5 2-5s1 2 2 5c1 5 2 11 7 11 5 0 9-5 10-12 1-5 1-9 3-13 2-4 4-8 4-13 0-7-5-12-12-12-6 0-9 4-14 4s-8-4-14-4z"
        fill="currentColor"
        stroke="currentColor"
        strokeWidth="1.5"
      />
      <path
        d="M32 14v11"
        stroke="white"
        strokeWidth="2"
        strokeLinecap="round"
      />
    </svg>
  );
}

const statusStyles: Record<string, string> = {
  waiting: "bg-warning/10 text-warning",
  entered: "bg-primary/10 text-primary",
  finished: "bg-success/10 text-success",
  cancelled: "bg-destructive/10 text-destructive",
  confirmed: "bg-success/10 text-success",
  pending: "bg-warning/10 text-warning",
};

type ClinicReservationsProps = {
  todayOnly?: boolean;
};

const toDateKey = (value?: string) => {
  if (!value) return null;
  const trimmed = value.trim();
  if (!trimmed) return null;

  // Common API date shape (YYYY-MM-DD or ISO datetime)
  const isoCandidate = trimmed.includes("T") ? trimmed.split("T")[0] : trimmed;
  if (/^\d{4}-\d{2}-\d{2}$/.test(isoCandidate)) {
    return isoCandidate;
  }

  // Common UI shape (DD/MM/YYYY or DD-MM-YYYY)
  const slashMatch = trimmed.match(/^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$/);
  if (slashMatch) {
    const [, day, month, year] = slashMatch;
    return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
  }

  const parsed = new Date(trimmed);
  if (Number.isNaN(parsed.getTime())) return null;
  return `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, "0")}-${String(parsed.getDate()).padStart(2, "0")}`;
};

const dateKeyFromDate = (date: Date) => {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
};

const toDateTimeLocal = (value?: string | null) => {
  if (!value) return "";
  if (value.includes(" ")) {
    const [d, t] = value.split(" ");
    return `${d}T${(t ?? "00:00:00").slice(0, 5)}`;
  }
  if (value.includes("T")) {
    const [d, t] = value.split("T");
    return `${d}T${(t ?? "00:00:00").slice(0, 5)}`;
  }
  return `${value}T00:00`;
};

const buildMonthGrid = (cursor: Date) => {
  const monthStart = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
  const monthEnd = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
  const gridStart = new Date(monthStart);
  gridStart.setDate(monthStart.getDate() - monthStart.getDay());
  const gridEnd = new Date(monthEnd);
  gridEnd.setDate(monthEnd.getDate() + (6 - monthEnd.getDay()));

  const days: Date[] = [];
  for (const d = new Date(gridStart); d <= gridEnd; d.setDate(d.getDate() + 1)) {
    days.push(new Date(d));
  }
  return days;
};

export default function ClinicReservations({ todayOnly = false }: ClinicReservationsProps) {
  const { t } = useLanguage();
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const navigate = useNavigate();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const perPage = 10;
  const [viewMode, setViewMode] = useState<"table" | "calendar">("table");
  const [calendarCursor, setCalendarCursor] = useState(() => new Date());
  const [draggedReservationId, setDraggedReservationId] = useState<string | number | null>(null);
  const [prescriptionOpen, setPrescriptionOpen] = useState(false);
  const [activeDrugPickerIndex, setActiveDrugPickerIndex] = useState<number | null>(null);
  const [prescriptionLoading, setPrescriptionLoading] = useState(false);
  const [raysOpen, setRaysOpen] = useState(false);
  const [raysLoading, setRaysLoading] = useState(false);
  const [raysHistory, setRaysHistory] = useState<ReservationRayRow[]>([]);
  const [rayForm, setRayForm] = useState<{
    date: string;
    payment: "paid" | "not_paid";
    report: string;
    cost: string;
    images: File[];
  }>({
    date: "",
    payment: "not_paid",
    report: "",
    cost: "",
    images: [],
  });
  const [activeReservation, setActiveReservation] = useState<ReservationRow | null>(null);
  const [glassesOpen, setGlassesOpen] = useState(false);
  const [glassesLoading, setGlassesLoading] = useState(false);
  const [glassesForm, setGlassesForm] = useState<GlassesDistanceForm>(emptyGlassesForm);
  const [glassesHistory, setGlassesHistory] = useState<Array<{ id: number | string; created_at?: string | null } & GlassesDistanceForm>>([]);
  const [teethOpen, setTeethOpen] = useState(false);
  const [teethLoading, setTeethLoading] = useState(false);
  const [sessionOpen, setSessionOpen] = useState(false);
  const [sessionBaseReservation, setSessionBaseReservation] = useState<ReservationRow | null>(null);
  const [sessionForm, setSessionForm] = useState<{
    date: string;
    reservation_value: string;
    payments: SessionPaymentRow[];
  }>({
    date: "",
    reservation_value: "",
    payments: [],
  });
  const [selectedTeeth, setSelectedTeeth] = useState<number[]>([]);
  const [toothNotes, setToothNotes] = useState<Record<number, string>>({});
  const [teethGeneralNote, setTeethGeneralNote] = useState("");
  const [teethNextPlan, setTeethNextPlan] = useState("");
  const [existingImages, setExistingImages] = useState<string[]>([]);
  const [newImages, setNewImages] = useState<File[]>([]);
  const [newImagePreviews, setNewImagePreviews] = useState<string[]>([]);
  const [prescriptionForm, setPrescriptionForm] = useState<{
    title: string;
    notes: string;
    drugs: DrugRow[];
  }>({
    title: "",
    notes: "",
    drugs: [{ selected_drug_id: undefined, name: "", type: "", dose: "", frequency: "", period: "", notes: "" }],
  });

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "reservations", todayOnly ? "today" : "all"],
    queryFn: () => clinicApi.reservations({ per_page: "500" }),
  });
  const clinicDrugsQuery = useQuery({
    queryKey: ["clinic", "drugs", "for-prescription"],
    queryFn: () => clinicApi.drugs({ per_page: "500" }),
  });

  const sessionReservationOptionsQuery = useQuery({
    queryKey: ["clinic", "session-reservation-options", sessionBaseReservation?.doctor_id, sessionForm.date],
    queryFn: () =>
      clinicApi.reservationOptions({
        doctor_id: String(sessionBaseReservation?.doctor_id ?? ""),
        date: sessionForm.date,
      }),
    enabled: Boolean(sessionOpen && sessionBaseReservation?.doctor_id && sessionForm.date),
  });
  const sessionContextQuery = useQuery({
    queryKey: ["clinic", "reservation-session-context", sessionBaseReservation?.id],
    queryFn: () => clinicApi.reservationSessionsContext(sessionBaseReservation!.id),
    enabled: Boolean(sessionOpen && sessionBaseReservation?.id),
  });

  const rows: ReservationRow[] = useMemo(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: ReservationRow[] })?.data ?? [];
  }, [data]);
  const clinicDrugOptions = useMemo<ClinicDrugOption[]>(() => {
    const root = (clinicDrugsQuery.data as { data?: unknown })?.data ?? clinicDrugsQuery.data;
    return (root as { data?: ClinicDrugOption[] })?.data ?? [];
  }, [clinicDrugsQuery.data]);
  const todayKey = toDateKey(new Date().toISOString());
  const dateScopedRows = todayOnly ? rows.filter((r) => toDateKey(r.date) === todayKey) : rows;
  const filtered = dateScopedRows.filter((r) =>
    `${r.patient_name ?? ""} ${r.doctor_name ?? ""} ${r.date ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);
  const calendarDays = useMemo(() => buildMonthGrid(calendarCursor), [calendarCursor]);
  const calendarItemsByDate = useMemo(() => {
    const map = new Map<string, ReservationRow[]>();
    filtered.forEach((row) => {
      const key = toDateKey(row.date);
      if (!key) return;
      const bucket = map.get(key) ?? [];
      bucket.push(row);
      map.set(key, bucket);
    });
    return map;
  }, [filtered]);
  const activeMonthLabel = useMemo(
    () => calendarCursor.toLocaleDateString(undefined, { month: "long", year: "numeric" }),
    [calendarCursor],
  );
  const sessionReservationOptions = useMemo(() => {
    const root = (sessionReservationOptionsQuery.data as { data?: unknown })?.data ?? sessionReservationOptionsQuery.data;
    const options = (root ?? {}) as {
      mode?: "numbers" | "slots";
      all_values?: string[];
      reserved_values?: string[];
      available_values?: string[];
    };
    return {
      mode: options.mode ?? "numbers",
      allValues: options.all_values ?? [],
      reservedValues: options.reserved_values ?? [],
      values: options.available_values ?? [],
    };
  }, [sessionReservationOptionsQuery.data]);
  const sessionContext = useMemo(() => {
    const root = (sessionContextQuery.data as { data?: unknown })?.data ?? sessionContextQuery.data;
    return ((root ?? {}) as SessionContextPayload);
  }, [sessionContextQuery.data]);
  const sessionBaseRemaining = Number(sessionContext.remaining ?? sessionBaseReservation?.remaining ?? 0);
  const sessionCanAddPayments = Boolean(sessionContext.can_add_payment ?? (sessionBaseRemaining > 0));
  const sessionPreviousRows = sessionContext.previous_sessions ?? [];
  const sessionHistoryRows = sessionContext.payment_history ?? [];
  const sessionPaymentsWithRemaining = useMemo(() => {
    let runningPaid = 0;
    return sessionForm.payments.map((row) => {
      runningPaid += Number(row.amount || 0);
      return {
        ...row,
        remaining: sessionBaseRemaining - runningPaid,
      };
    });
  }, [sessionForm.payments, sessionBaseRemaining]);

  const savePrescriptionMutation = useMutation({
    mutationFn: async () => {
      if (!activeReservation) return;
      const normalizedDrugs = prescriptionForm.drugs
        .map((d) => ({
          selected_drug_id: d.selected_drug_id ? String(d.selected_drug_id) : undefined,
          name: d.name.trim(),
          type: d.type.trim(),
          dose: d.dose.trim(),
          frequency: d.frequency.trim(),
          period: d.period.trim(),
          notes: d.notes.trim(),
        }))
        .filter((d) => d.name || d.type || d.dose || d.frequency || d.period || d.notes);

      if (normalizedDrugs.length === 0) {
        throw new Error("Add at least one drug row.");
      }

      for (const d of normalizedDrugs) {
        if (!d.name || !d.type || !d.dose || !d.frequency || !d.period) {
          throw new Error("Drug name, type, dose, frequency and period are required.");
        }
      }

      const formData = new FormData();
      formData.append("title", prescriptionForm.title.trim());
      formData.append("notes", prescriptionForm.notes.trim());
      formData.append("drugs", JSON.stringify(normalizedDrugs));
      newImages.forEach((file) => formData.append("images[]", file));
      return clinicApi.saveReservationPrescription(activeReservation.id, formData);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "reservations"] });
      toast({ title: t("clinic.reservations.PrescriptionSaved") });
      setPrescriptionOpen(false);
    },
    onError: (e) => {
      toast({
        title: t("clinic.reservations.FailedToSavePrescription"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const moveReservationMutation = useMutation({
    mutationFn: async (input: { reservation: ReservationRow; targetDate: string }) => {
      const { reservation, targetDate } = input;
      if (!reservation.patient_id || !reservation.doctor_id) {
        throw new Error("Reservation is missing patient or doctor data.");
      }

      return clinicApi.updateReservation(reservation.id, {
        patient_id: Number(reservation.patient_id),
        doctor_id: Number(reservation.doctor_id),
        date: targetDate,
        reservation_number: reservation.reservation_number ?? undefined,
        slot: reservation.slot ?? reservation.time ?? undefined,
        status: (reservation.status as "waiting" | "entered" | "finished" | "cancelled" | undefined) ?? "waiting",
        acceptance: (reservation.acceptance as "pending" | "approved" | "not_approved" | undefined) ?? "pending",
        payment: (reservation.payment as "paid" | "not_paid" | "partially_paid" | "unpaid" | undefined) ?? "not_paid",
        month: targetDate.slice(5, 7),
      });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "reservations"] });
      toast({ title: "Reservation moved successfully" });
    },
    onError: (err) => {
      toast({
        title: "Failed to move reservation",
        description: err instanceof Error ? err.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const createSessionMutation = useMutation({
    mutationFn: async () => {
      if (!sessionBaseReservation) throw new Error("Parent reservation not selected.");
      if (!sessionForm.date) throw new Error("Session date is required.");
      if (!sessionForm.reservation_value) throw new Error("Reservation number or slot is required.");

      return clinicApi.createReservationSession(sessionBaseReservation.id, {
        date: sessionForm.date,
        reservation_number: sessionReservationOptions.mode === "numbers" ? sessionForm.reservation_value : undefined,
        slot: sessionReservationOptions.mode === "slots" ? sessionForm.reservation_value : undefined,
        payments: sessionForm.payments
          .filter((row) => row.date && row.amount !== "")
          .map((row, index) => ({
            date: row.date,
            amount: Number(row.amount || 0),
            remaining: Math.max(0, Number(sessionPaymentsWithRemaining[index]?.remaining ?? sessionBaseRemaining)),
            payment_way: row.payment_way,
          })),
      });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "reservations"] });
      toast({ title: "Session created" });
      setSessionOpen(false);
      setSessionBaseReservation(null);
      setSessionForm({ date: "", reservation_value: "", payments: [] });
    },
    onError: (e) => {
      toast({
        title: "Failed to create session",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const saveRayMutation = useMutation({
    mutationFn: async () => {
      if (!activeReservation) return;
      if (!rayForm.date) throw new Error("Date is required.");
      return clinicApi.createReservationRay(activeReservation.id, toRayFormData(rayForm));
    },
    onSuccess: () => {
      toast({ title: "Ray saved" });
      if (!activeReservation) {
        setRaysOpen(false);
        return;
      }
      void (async () => {
        const res = await clinicApi.reservationRays(activeReservation.id);
        const root = (res as { data?: unknown })?.data ?? res;
        setRaysHistory(Array.isArray(root) ? (root as ReservationRayRow[]) : []);
        setRayForm((prev) => ({ ...prev, report: "", cost: "", images: [] }));
      })();
    },
    onError: (e) => {
      toast({
        title: "Failed to save ray",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const handleDropOnDay = (day: Date) => {
    if (!draggedReservationId) return;
    const dragged = filtered.find((r) => String(r.id) === String(draggedReservationId));
    setDraggedReservationId(null);
    if (!dragged) return;

    const targetDate = dateKeyFromDate(day);
    if (toDateKey(dragged.date) === targetDate) return;
    moveReservationMutation.mutate({ reservation: dragged, targetDate });
  };

  const openPrescriptionModal = async (reservation: ReservationRow) => {
    setActiveReservation(reservation);
    setPrescriptionOpen(true);
    setPrescriptionLoading(true);
    setExistingImages([]);
    setNewImages([]);
    setNewImagePreviews([]);
    try {
      const res = await clinicApi.reservationPrescription(reservation.id);
      const root = (res as { data?: unknown })?.data ?? {};
      const dataObj = (root && typeof root === "object" ? root : {}) as {
        title?: string | null;
        notes?: string | null;
        drugs?: Array<{
          drug_id?: number | string | null;
          name?: string;
          type?: string;
          dose?: string;
          frequency?: string;
          period?: string;
          notes?: string | null;
        }>;
        images?: string[];
      };
      const drugs = Array.isArray(dataObj.drugs) && dataObj.drugs.length > 0
        ? dataObj.drugs.map((d) => ({
            selected_drug_id: d.drug_id != null && d.drug_id !== "" ? String(d.drug_id) : undefined,
            name: String(d.name ?? ""),
            type: String(d.type ?? ""),
            dose: String(d.dose ?? ""),
            frequency: String(d.frequency ?? ""),
            period: String(d.period ?? ""),
            notes: String(d.notes ?? ""),
          }))
        : [{ selected_drug_id: undefined, name: "", type: "", dose: "", frequency: "", period: "", notes: "" }];

      setPrescriptionForm({
        title: String(dataObj.title ?? ""),
        notes: String(dataObj.notes ?? ""),
        drugs,
      });
      setExistingImages(Array.isArray(dataObj.images) ? dataObj.images : []);
    } catch (e) {
      toast({
        title: t("clinic.reservations.FailedToLoadPrescription"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    } finally {
      setPrescriptionLoading(false);
    }
  };

  const openRaysModal = async (reservation: ReservationRow) => {
    if (!reservation.patient_id) {
      toast({
        title: "Cannot create ray",
        description: "Reservation is missing patient data.",
        variant: "destructive",
      });
      return;
    }
    setActiveReservation(reservation);
    setRayForm({
      date: toDateKey(reservation.date) ?? new Date().toISOString().slice(0, 10),
      payment: "not_paid",
      report: "",
      cost: "",
      images: [],
    });
    setRaysOpen(true);
    setRaysLoading(true);
    try {
      const res = await clinicApi.reservationRays(reservation.id);
      const root = (res as { data?: unknown })?.data ?? res;
      setRaysHistory(Array.isArray(root) ? (root as ReservationRayRow[]) : []);
    } catch (e) {
      toast({
        title: "Failed to load rays",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    } finally {
      setRaysLoading(false);
    }
  };

  const saveGlassesMutation = useMutation({
    mutationFn: async () => {
      if (!activeReservation) return;
      const payload = Object.fromEntries(
        Object.entries(glassesForm).map(([key, value]) => [key, value.trim() || undefined]),
      ) as Record<string, string | undefined>;
      return clinicApi.createReservationGlassesDistance(activeReservation.id, payload);
    },
    onSuccess: async () => {
      toast({ title: t("clinic.reservations.GlassesDistanceSaved") });
      if (!activeReservation) return;
      const res = await clinicApi.reservationGlassesDistances(activeReservation.id);
      const root = (res as { data?: unknown })?.data ?? res;
      setGlassesHistory(Array.isArray(root) ? (root as Array<{ id: number | string; created_at?: string | null } & GlassesDistanceForm>) : []);
      setGlassesForm(emptyGlassesForm());
    },
    onError: (e) => {
      toast({
        title: t("clinic.reservations.FailedToSaveGlassesDistance"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const openGlassesModal = async (reservation: ReservationRow) => {
    setActiveReservation(reservation);
    setGlassesOpen(true);
    setGlassesLoading(true);
    setGlassesForm(emptyGlassesForm());
    try {
      const res = await clinicApi.reservationGlassesDistances(reservation.id);
      const root = (res as { data?: unknown })?.data ?? res;
      setGlassesHistory(Array.isArray(root) ? (root as Array<{ id: number | string; created_at?: string | null } & GlassesDistanceForm>) : []);
    } catch (e) {
      toast({
        title: t("clinic.reservations.FailedToLoadGlassesDistance"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    } finally {
      setGlassesLoading(false);
    }
  };

  const saveTeethMutation = useMutation({
    mutationFn: async () => {
      if (!activeReservation) return;
      return clinicApi.saveReservationTeeth(activeReservation.id, {
        general_note: teethGeneralNote.trim() || undefined,
        next_session_plan: teethNextPlan.trim() || undefined,
        teeth: selectedTeeth.map((n) => ({
          tooth_number: n,
          tooth_note: (toothNotes[n] ?? "").trim() || undefined,
        })),
      });
    },
    onSuccess: () => {
      toast({ title: t("clinic.reservations.ReservationTeethSaved") });
      setTeethOpen(false);
    },
    onError: (e) => {
      toast({
        title: t("clinic.reservations.FailedToSaveReservationTeeth"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const openTeethModal = async (reservation: ReservationRow) => {
    setActiveReservation(reservation);
    setTeethOpen(true);
    setTeethLoading(true);
    setSelectedTeeth([]);
    setToothNotes({});
    setTeethGeneralNote("");
    setTeethNextPlan("");
    try {
      const res = await clinicApi.reservationTeeth(reservation.id);
      const root = (res as { data?: unknown })?.data ?? {};
      const dataObj = (root && typeof root === "object" ? root : {}) as {
        general_note?: string | null;
        next_session_plan?: string | null;
        teeth?: ReservationToothRow[];
      };
      const items = Array.isArray(dataObj.teeth) ? dataObj.teeth : [];
      setSelectedTeeth(items.map((i) => Number(i.tooth_number)).filter((n) => Number.isFinite(n)));
      const notes: Record<number, string> = {};
      items.forEach((item) => {
        const num = Number(item.tooth_number);
        if (!Number.isFinite(num)) return;
        notes[num] = String(item.tooth_note ?? "");
      });
      setToothNotes(notes);
      setTeethGeneralNote(String(dataObj.general_note ?? ""));
      setTeethNextPlan(String(dataObj.next_session_plan ?? ""));
    } catch (e) {
      toast({
        title: t("clinic.reservations.FailedToLoadReservationTeeth"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    } finally {
      setTeethLoading(false);
    }
  };

  const openSessionModal = (reservation: ReservationRow) => {
    setSessionBaseReservation(reservation);
    setSessionForm({
      date: toDateKey(reservation.date) ?? new Date().toISOString().slice(0, 10),
      reservation_value: "",
      payments: Number(reservation.remaining ?? 0) > 0
        ? [{ date: toDateTimeLocal(reservation.date), amount: "", payment_way: "cash" }]
        : [],
    });
    setSessionOpen(true);
  };

  useEffect(() => {
    if (!sessionOpen) return;
    if (!sessionCanAddPayments) {
      setSessionForm((prev) => ({ ...prev, payments: [] }));
      return;
    }
    if (sessionForm.payments.length > 0) return;
    setSessionForm((prev) => ({
      ...prev,
      payments: [{ date: `${prev.date}T00:00`, amount: "", payment_way: "cash" }],
    }));
  }, [sessionOpen, sessionCanAddPayments, sessionForm.payments.length]);

  useEffect(() => {
    return () => {
      newImagePreviews.forEach((url) => URL.revokeObjectURL(url));
    };
  }, [newImagePreviews]);

  const addDrugRow = () => {
    setPrescriptionForm((prev) => ({
      ...prev,
      drugs: [...prev.drugs, { selected_drug_id: undefined, name: "", type: "", dose: "", frequency: "", period: "", notes: "" }],
    }));
  };

  const removeDrugRow = (idx: number) => {
    setPrescriptionForm((prev) => ({
      ...prev,
      drugs: prev.drugs.filter((_, i) => i !== idx),
    }));
  };

  const applyClinicDrugToRow = (rowIndex: number, drugId: string) => {
    const selected = clinicDrugOptions.find((item) => String(item.id) === drugId);
    if (!selected) return;
    setPrescriptionForm((prev) => ({
      ...prev,
      drugs: prev.drugs.map((drug, idx) =>
        idx === rowIndex
          ? {
              ...drug,
              selected_drug_id: String(selected.id),
              name: String(selected.name ?? ""),
              type: String(selected.type ?? ""),
              dose: String(selected.dose ?? ""),
              frequency: String(selected.frequency ?? ""),
              period: String(selected.period ?? ""),
              notes: String(selected.notes ?? ""),
            }
          : drug,
      ),
    }));
  };

  const onPickImages = (files: FileList | null) => {
    if (!files) return;
    const list = Array.from(files);
    const previews = list.map((file) => URL.createObjectURL(file));
    setNewImages(list);
    setNewImagePreviews((prev) => {
      prev.forEach((url) => URL.revokeObjectURL(url));
      return previews;
    });
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
        <h2 className="text-2xl font-bold">{todayOnly ? t("clinic.reservations.TodayReservations") : t("clinic.reservations.Reservations")}</h2>
       
        </div>
        <Button asChild className="gradient-primary text-primary-foreground border-0 gap-2">
          <Link to="/clinic-dashboard/patients">
            <Plus className="h-4 w-4" />
            {t("clinic.reservations.AddReservation")}
          </Link>
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.reservations.search")}
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-10"
        />
      </div>

      <div className="mb-4 flex items-center gap-2">
        <Button variant={viewMode === "table" ? "default" : "outline"} size="sm" onClick={() => setViewMode("table")}>
          {t("clinic.reservations.Reservations")}
        </Button>
        {!todayOnly && (
          <Button variant={viewMode === "calendar" ? "default" : "outline"} size="sm" onClick={() => setViewMode("calendar")} className="gap-2">
            <CalendarDays className="h-4 w-4" />
            Calendar
          </Button>
        )}
      </div>

      {viewMode === "calendar" && !todayOnly && (
        <div className="bg-card rounded-xl border shadow-card overflow-hidden mb-6">
          <div className="flex items-center justify-between px-4 py-3 border-b">
            <Button
              variant="outline"
              size="icon"
              onClick={() => setCalendarCursor((prev) => new Date(prev.getFullYear(), prev.getMonth() - 1, 1))}
              aria-label="Previous month"
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <p className="font-semibold">{activeMonthLabel}</p>
            <Button
              variant="outline"
              size="icon"
              onClick={() => setCalendarCursor((prev) => new Date(prev.getFullYear(), prev.getMonth() + 1, 1))}
              aria-label="Next month"
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
          <div className="grid grid-cols-7 border-b text-xs font-medium text-muted-foreground">
            {["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].map((name) => (
              <div key={name} className="px-2 py-2 border-r last:border-r-0">{name}</div>
            ))}
          </div>
          <div className="grid grid-cols-7">
            {calendarDays.map((day) => {
              const dayKey = dateKeyFromDate(day);
              const isCurrentMonth = day.getMonth() === calendarCursor.getMonth();
              const dayItems = calendarItemsByDate.get(dayKey) ?? [];
              return (
                <div
                  key={dayKey}
                  className={`min-h-28 border-r border-b last:border-r-0 p-2 ${isCurrentMonth ? "bg-background" : "bg-muted/20"}`}
                  onDragOver={(e) => e.preventDefault()}
                  onDrop={() => handleDropOnDay(day)}
                >
                  <div className={`text-xs mb-2 ${isCurrentMonth ? "text-foreground" : "text-muted-foreground"}`}>{day.getDate()}</div>
                  <div className="space-y-1">
                    {dayItems.slice(0, 3).map((item) => (
                      <div
                        key={String(item.id)}
                        className="rounded-md border bg-primary/10 text-primary px-2 py-1 text-[11px] cursor-pointer"
                        draggable
                        onDragStart={() => setDraggedReservationId(item.id)}
                        onClick={() => navigate(`/clinic-dashboard/reservations/${item.id}/edit`)}
                        title="Click to edit, drag to move"
                      >
                        <p className="truncate font-medium">{item.patient_name ?? "Patient"}</p>
                        <p className="truncate text-[10px] text-muted-foreground">{item.doctor_name ?? "Doctor"} {item.time ? `- ${item.time}` : ""}</p>
                      </div>
                    ))}
                    {dayItems.length > 3 && (
                      <p className="text-[10px] text-muted-foreground">+{dayItems.length - 3} more</p>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {(viewMode === "table" || todayOnly) && (
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Patient")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Doctor")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Date")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.NumberSlot")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Status")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Acceptance")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Payment")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations.Actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={9}>{t("clinic.reservations.LoadingReservations")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={9}>
                    {error instanceof Error ? error.message : t("clinic.reservations.FailedToLoadReservations")}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={9}>
                    {todayOnly ? t("clinic.reservations.NoReservationsScheduledForToday") : t("clinic.reservations.NoReservationsFound")}
                  </td>
                </tr>
              )}
              {paged.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">
                    {r.patient_name ?? "—"}
                    {(r.type ?? "reservation") === "session" ? (
                      <Badge variant="secondary" className="ml-2">session</Badge>
                    ) : null}
                  </td>
                  <td className="p-4 text-muted-foreground">{r.doctor_name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.date ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.reservation_number ?? r.slot ?? r.time ?? "—"}</td>
                  <td className="p-4">
                    <Badge variant="secondary" className={statusStyles[(r.status ?? "").toLowerCase()] ?? ""}>
                      {r.status ?? "—"}
                    </Badge>
                  </td>
                  <td className="p-4 text-muted-foreground">{r.acceptance ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.payment === "not_paid" ? "unpaid" : r.payment ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      {(r.type ?? "reservation") === "reservation" ? (
                        <Button variant="outline" size="sm" onClick={() => openSessionModal(r)}>
                         {t("clinic.reservations.AddSession")}
                        </Button>
                      ) : null}
                      <Button asChild variant="outline" size="sm" className="gap-2">
                        <Link to={`/clinic-dashboard/reservations/${r.id}/edit`}>
                          <Edit className="h-4 w-4" />
                          {t("clinic.reservations.Edit")}
                        </Link>
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => void openPrescriptionModal(r)}>
                        {t("clinic.reservations.Prescription")}
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => void openRaysModal(r)}>
                        {t("clinic.reservations.Rays")}
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => void openGlassesModal(r)} className="gap-2">
                        <Glasses className="h-4 w-4" />
                        {t("clinic.reservations.Glasses")}
                      </Button>
                      <Button variant="outline" size="sm" onClick={() => void openTeethModal(r)} className="gap-2">
                        <Smile className="h-4 w-4" />
                        {t("clinic.reservations.Teeth")}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">{t("clinic.reservations.Page")} {safePage} {t("clinic.reservations.Of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                {t("clinic.reservations.Previous")}
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
              >
                {t("clinic.reservations.Next")}
              </Button>
            </div>
          </div>
        )}
      </div>
      )}

      <Dialog open={sessionOpen} onOpenChange={setSessionOpen}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>
              {t("clinic.reservations.AddSession")} {sessionBaseReservation ? `for Reservation #${sessionBaseReservation.id}` : ""}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-2">
            {sessionContextQuery.isLoading ? (
              <p className="text-sm text-muted-foreground">{t("clinic.reservations.LoadingReservationSessionContext")}</p>
            ) : null}
            <div className="rounded-md border p-3 text-sm">
              <p><span className="font-medium">{t("clinic.reservations.Patient")}:</span> {sessionContext.reservation?.patient_name ?? sessionBaseReservation?.patient_name ?? "—"}</p>
              <p><span className="font-medium">{t("clinic.reservations.Doctor")}:</span> {sessionContext.reservation?.doctor_name ?? sessionBaseReservation?.doctor_name ?? "—"}</p>
              <p><span className="font-medium">{t("clinic.reservations.ReservationCost")}:</span> {sessionContext.reservation?.cost ?? sessionBaseReservation?.cost ?? 0}</p>
              <p><span className="font-medium">{t("clinic.reservations.Remaining")}:</span> {sessionBaseRemaining}</p>
            </div>
            {sessionPreviousRows.length > 0 ? (
              <div className="space-y-2">
                <Label>{t("clinic.reservations.PreviousSessions")}</Label>
                <div className="max-h-36 overflow-y-auto rounded-md border">
                  {sessionPreviousRows.map((session) => (
                    <div key={String(session.id)} className="flex items-center justify-between gap-2 border-b px-3 py-2 text-xs last:border-b-0">
                      <div>
                        <p className="font-medium">#{session.id} - {session.date ?? "—"}</p>
                        <p className="text-muted-foreground">{session.reservation_number ?? session.slot ?? "—"}</p>
                      </div>
                      <div className="text-right">
                        <p>{session.payment ?? "—"}</p>
                        <p className="text-muted-foreground">Remaining: {session.remaining ?? 0}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ) : null}
            {sessionHistoryRows.length > 0 ? (
              <div className="space-y-2">
                <Label>{t("clinic.reservations.PaymentHistory")}</Label>
                <div className="max-h-36 overflow-y-auto rounded-md border">
                  {sessionHistoryRows.map((row) => (
                    <div key={String(row.id)} className="flex items-center justify-between gap-2 border-b px-3 py-2 text-xs last:border-b-0">
                      <div>
                        <p className="font-medium">{row.date ?? "—"}</p>
                        <p className="text-muted-foreground">{row.module_type ?? "Reservation"} #{row.module_id ?? "—"}</p>
                      </div>
                      <div className="text-right">
                        <p>{t("clinic.reservations.Amount")}: {row.amount ?? 0}</p>
                        <p className="text-muted-foreground">{t("clinic.reservations.Remaining")}: {row.remaining ?? 0}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ) : null}
            <div className="grid sm:grid-cols-2 gap-3">
              <div className="space-y-1">
                <Label>{t("clinic.reservations.Date")}</Label>
                <Input
                  type="date"
                  value={sessionForm.date}
                  onChange={(e) => setSessionForm((prev) => ({ ...prev, date: e.target.value, reservation_value: "" }))}
                />
              </div>
              <div className="space-y-1">
                <Label>{sessionReservationOptions.mode === "slots" ? t("clinic.reservations.Slot") : t("clinic.reservations.ReservationNumber")}</Label>
                {(sessionReservationOptions.allValues.length > 0 ? sessionReservationOptions.allValues : sessionReservationOptions.values).length > 0 ? (
                  <select
                    title={t("clinic.reservations.SessionReservationValue")}
                    className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                    value={sessionForm.reservation_value}
                    onChange={(e) => setSessionForm((prev) => ({ ...prev, reservation_value: e.target.value }))}
                  >
                    <option value="">{t("clinic.reservations.Select")}...</option>
                    {(sessionReservationOptions.allValues.length > 0 ? sessionReservationOptions.allValues : sessionReservationOptions.values).map((value) => (
                      <option key={value} value={value} disabled={sessionReservationOptions.reservedValues.includes(value)}>
                        {value}
                      </option>
                    ))}
                  </select>
                ) : (
                  <Input
                    value={sessionForm.reservation_value}
                    onChange={(e) => setSessionForm((prev) => ({ ...prev, reservation_value: e.target.value }))}
                    placeholder={sessionReservationOptionsQuery.isLoading ? "Loading options..." : "Enter value"}
                  />
                )}
              </div>
            </div>

            {sessionCanAddPayments ? (
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label>{t("clinic.reservations.Payments")}</Label>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      setSessionForm((prev) => ({
                        ...prev,
                        payments: [...prev.payments, { date: `${sessionForm.date}T00:00`, amount: "", payment_way: "cash" }],
                      }))
                    }
                  >
                    {t("clinic.reservations.AddPayment")}
                  </Button>
                </div>
                {sessionForm.payments.length === 0 ? (
                  <p className="text-sm text-muted-foreground">{t("clinic.reservations.NoPaymentRowsYet")}</p>
                ) : (
                  <div className="space-y-2">
                    {sessionForm.payments.map((row, index) => (
                      <div key={index} className="rounded-md border p-2 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                        <div className="space-y-1">
                          <Label>{t("clinic.reservations.Date")}</Label>
                          <Input
                            type="datetime-local"
                            value={row.date}
                            onChange={(e) =>
                              setSessionForm((prev) => ({
                                ...prev,
                                payments: prev.payments.map((item, i) => (i === index ? { ...item, date: e.target.value } : item)),
                              }))
                            }
                          />
                        </div>
                        <div className="space-y-1">
                          <Label>{t("clinic.reservations.Amount")}</Label>
                          <Input
                            type="number"
                            min="0"
                            step="0.01"
                            value={row.amount}
                            onChange={(e) =>
                              setSessionForm((prev) => ({
                                ...prev,
                                payments: prev.payments.map((item, i) => (i === index ? { ...item, amount: e.target.value } : item)),
                              }))
                            }
                          />
                        </div>
                        <div className="space-y-1">
                          <Label>{t("clinic.reservations.PaymentWay")}</Label>
                          <select
                            title="Session payment way"
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            value={row.payment_way}
                            onChange={(e) =>
                              setSessionForm((prev) => ({
                                ...prev,
                                payments: prev.payments.map((item, i) => (i === index ? { ...item, payment_way: e.target.value as "cash" } : item)),
                              }))
                            }
                          >
                            <option value="cash">{t("clinic.reservations.Cash")}</option>
                          </select>
                        </div>
                        <div className="space-y-1">
                          <Label>{t("clinic.reservations.Remaining")}</Label>
                          <div className="h-10 rounded-md border px-3 py-2 text-sm text-muted-foreground flex items-center justify-between">
                            <span>{sessionPaymentsWithRemaining[index]?.remaining ?? sessionBaseRemaining}</span>
                            <button
                              type="button"
                              className="text-destructive text-xs underline"
                              onClick={() =>
                                setSessionForm((prev) => ({
                                  ...prev,
                                  payments: prev.payments.filter((_, i) => i !== index),
                                }))
                              }
                            >
                              {t("clinic.reservations.Remove")}
                            </button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground">{t("clinic.reservations.ReservationIsAlreadyFullyPaidNoNewPaymentRequired")}</p>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setSessionOpen(false)} disabled={createSessionMutation.isPending}>
              {t("clinic.reservations.Cancel")}
            </Button>
            <Button
              onClick={() => createSessionMutation.mutate()}
              disabled={createSessionMutation.isPending}
              className="gradient-primary text-primary-foreground border-0"
            >
              {createSessionMutation.isPending ? t("clinic.reservations.Saving") : t("clinic.reservations.CreateSession")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={prescriptionOpen} onOpenChange={setPrescriptionOpen}>
        <DialogContent className="sm:max-w-4xl">
          <DialogHeader>
            <DialogTitle>
              {t("clinic.reservations.Prescription")} {activeReservation ? `${t("clinic.reservations.ForReservation")} #${activeReservation.id}` : ""}
            </DialogTitle>
          </DialogHeader>
          {prescriptionLoading ? (
            <p className="text-sm text-muted-foreground">{t("clinic.reservations.LoadingPrescription")}</p>
          ) : (
            <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
              <div className="grid sm:grid-cols-2 gap-3">
                <div className="space-y-1">
                  <Label htmlFor="rx-title">{t("clinic.reservations.PrescriptionTitle")}</Label>
                  <Input
                    id="rx-title"
                    value={prescriptionForm.title}
                    onChange={(e) => setPrescriptionForm((prev) => ({ ...prev, title: e.target.value }))}
                  />
                </div>
                <div className="space-y-1">
                  <Label htmlFor="rx-images">{t("clinic.reservations.PrescriptionImages")}</Label>
                  <Input id="rx-images" type="file" multiple accept="image/*" onChange={(e) => onPickImages(e.target.files)} />
                </div>
              </div>

              <div className="space-y-1">
                <Label htmlFor="rx-notes">{t("clinic.reservations.Notes")}</Label>
                <Textarea
                  id="rx-notes"
                  rows={2}
                  value={prescriptionForm.notes}
                  onChange={(e) => setPrescriptionForm((prev) => ({ ...prev, notes: e.target.value }))}
                />
              </div>

              {(existingImages.length > 0 || newImagePreviews.length > 0) && (
                <div className="space-y-2">
                  <Label>{t("clinic.reservations.ImagesPreview")}</Label>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                    {existingImages.map((url) => (
                      <img key={url} src={url} alt="Existing prescription" className="w-full h-28 object-cover rounded border" />
                    ))}
                    {newImagePreviews.map((url) => (
                      <img key={url} src={url} alt="New prescription" className="w-full h-28 object-cover rounded border" />
                    ))}
                  </div>
                </div>
              )}

              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label>{t("clinic.reservations.Drugs")}</Label>
                  <Button type="button" variant="outline" size="sm" onClick={addDrugRow}>
                    {t("clinic.reservations.AddDrug")}
                  </Button>
                </div>
                {prescriptionForm.drugs.map((drug, idx) => (
                  <div key={idx} className="border rounded-lg p-3 space-y-2">
                    <div className="grid sm:grid-cols-2 lg:grid-cols-7 gap-2 items-end">
                      <div className="space-y-2 col-span-2">
                        <Label>{t("clinic.reservations.Drugs")}</Label>
                        <Popover open={activeDrugPickerIndex === idx} onOpenChange={(open) => setActiveDrugPickerIndex(open ? idx : null)}>
                          <PopoverTrigger asChild>
                            <Button variant="outline" role="combobox" className="w-full justify-between">
                              {drug.selected_drug_id
                                ? (clinicDrugOptions.find((item) => String(item.id) === String(drug.selected_drug_id))?.name ?? `Drug #${drug.selected_drug_id}`)
                                : (clinicDrugsQuery.isLoading ? "Loading clinic drugs..." : "Select clinic drug")}
                              <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                          </PopoverTrigger>
                          <PopoverContent className="w-[420px] p-0" align="start">
                            <Command>
                              <CommandInput placeholder="Search clinic drugs..." />
                              <CommandEmpty>No drug found.</CommandEmpty>
                              <CommandGroup className="max-h-64 overflow-y-auto">
                                {clinicDrugOptions.map((item) => (
                                  <CommandItem
                                    key={String(item.id)}
                                    value={`${item.name ?? ""} ${item.type ?? ""} ${item.dose ?? ""} ${item.frequency ?? ""} ${item.period ?? ""} ${String(item.id)}`}
                                    onSelect={() => {
                                      applyClinicDrugToRow(idx, String(item.id));
                                      setActiveDrugPickerIndex(null);
                                    }}
                                  >
                                    <Check
                                      className={cn(
                                        "mr-2 h-4 w-4",
                                        String(drug.selected_drug_id ?? "") === String(item.id) ? "opacity-100" : "opacity-0",
                                      )}
                                    />
                                    <span className="truncate">{item.name ?? `Drug ${item.id}`} - {item.type ?? "—"}</span>
                                  </CommandItem>
                                ))}
                              </CommandGroup>
                            </Command>
                          </PopoverContent>
                        </Popover>
                      </div>
                      <div className="space-y-1">
                        <Label>{t("clinic.reservations.Name")}</Label>
                        <Input
                          value={drug.name}
	                disabled={drug.selected_drug_id !== undefined}
                          onChange={(e) =>
                            setPrescriptionForm((prev) => ({
                              ...prev,
                              drugs: prev.drugs.map((d, i) => (i === idx ? { ...d, selected_drug_id: undefined, name: e.target.value } : d)),
                            }))
                          }
                        />
                      </div>
                      <div className="space-y-1">
                        <Label>{t("clinic.reservations.Type")}</Label>
                        <Input
                          value={drug.type}
                          onChange={(e) =>
                            setPrescriptionForm((prev) => ({
                              ...prev,
                              drugs: prev.drugs.map((d, i) => (i === idx ? { ...d, type: e.target.value } : d)),
                            }))
                          }
                        />
                      </div>
                      <div className="space-y-1">
                        <Label>{t("clinic.reservations.Dose")}</Label>
                        <Input
                          value={drug.dose}
                          onChange={(e) =>
                            setPrescriptionForm((prev) => ({
                              ...prev,
                              drugs: prev.drugs.map((d, i) => (i === idx ? { ...d, dose: e.target.value } : d)),
                            }))
                          }
                        />
                      </div>
                      <div className="space-y-1">
                        <Label>{t("clinic.reservations.Frequency")}</Label>
                        <Input
                          value={drug.frequency}
                          onChange={(e) =>
                            setPrescriptionForm((prev) => ({
                              ...prev,
                              drugs: prev.drugs.map((d, i) => (i === idx ? { ...d, frequency: e.target.value } : d)),
                            }))
                          }
                        />
                      </div>
                      <div className="flex items-end justify-end h-full">
	<div className="space-y-1">
                        <Label>{t("clinic.reservations.Period")}</Label>
                        <Input
                          value={drug.period}
                          onChange={(e) =>
                            setPrescriptionForm((prev) => ({
                              ...prev,
                              drugs: prev.drugs.map((d, i) => (i === idx ? { ...d, period: e.target.value } : d)),
                            }))
                          }
                        />
                      </div>
                      <div className="w-[20px] flex items-end justify-end h-full">
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          onClick={() => removeDrugRow(idx)}
                          disabled={prescriptionForm.drugs.length <= 1}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
                        </Button>
                      </div>
	</div>
                    </div>
                    <Textarea
                      rows={2}
                      placeholder={t("clinic.reservations.DrugNotes")}
                      value={drug.notes}
                      onChange={(e) =>
                        setPrescriptionForm((prev) => ({
                          ...prev,
                          drugs: prev.drugs.map((d, i) => (i === idx ? { ...d, notes: e.target.value } : d)),
                        }))
                      }
                    />
                  </div>
                ))}
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setPrescriptionOpen(false)} disabled={savePrescriptionMutation.isPending}>
              {t("clinic.reservations.Cancel")}
            </Button>
            <Button
              onClick={() => savePrescriptionMutation.mutate()}
              disabled={savePrescriptionMutation.isPending || prescriptionLoading}
              className="gradient-primary text-primary-foreground border-0"
            >
              {savePrescriptionMutation.isPending ? t("clinic.reservations.Saving") : t("clinic.reservations.SavePrescription")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <Dialog open={raysOpen} onOpenChange={setRaysOpen}>
        <DialogContent className="sm:max-w-3xl max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>
              Rays {activeReservation ? `for Reservation #${activeReservation.id}` : ""}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-2">
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>Date</Label>
                <Input
                  type="date"
                  value={rayForm.date}
                  onChange={(e) => setRayForm((prev) => ({ ...prev, date: e.target.value }))}
                />
              </div>
              <div className="space-y-2">
                <Label>Payment</Label>
                <select
                  title="Ray payment"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={rayForm.payment}
                  onChange={(e) => setRayForm((prev) => ({ ...prev, payment: e.target.value as "paid" | "not_paid" }))}
                >
                  <option value="not_paid">not_paid</option>
                  <option value="paid">paid</option>
                </select>
              </div>
              <div className="space-y-2">
                <Label>Cost (optional)</Label>
                <Input
                  type="number"
                  min="0"
                  value={rayForm.cost}
                  onChange={(e) => setRayForm((prev) => ({ ...prev, cost: e.target.value }))}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Report</Label>
              <Textarea
                rows={3}
                value={rayForm.report}
                onChange={(e) => setRayForm((prev) => ({ ...prev, report: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>Media</Label>
              <Input
                type="file"
                multiple
                accept="image/*"
                onChange={(e) => setRayForm((prev) => ({ ...prev, images: Array.from(e.target.files ?? []) }))}
              />
            </div>
            <div className="space-y-2">
              <Label>Recent Rays</Label>
              {raysLoading ? (
                <p className="text-sm text-muted-foreground">Loading...</p>
              ) : raysHistory.length === 0 ? (
                <p className="text-sm text-muted-foreground">No rays yet.</p>
              ) : (
                <div className="space-y-2">
                  {raysHistory.slice(0, 5).map((row) => (
                    <div key={String(row.id)} className="rounded-md border p-2 text-sm">
                      <p className="text-muted-foreground text-xs">{row.created_at ?? row.date ?? "—"}</p>
                      <p><span className="font-medium">Payment:</span> {row.payment ?? "not_paid"} {row.cost ? `| Cost: ${row.cost}` : ""}</p>
                      <p><span className="font-medium">Report:</span> {row.report ?? "—"}</p>
                      {(row.images ?? []).length > 0 ? (
                        <div className="mt-1 flex flex-wrap gap-2">
                          {(row.images ?? []).map((url, idx) => (
                            <a key={`${String(row.id)}-${idx}`} href={url} target="_blank" rel="noreferrer" className="text-xs underline text-primary">
                              View image {idx + 1}
                            </a>
                          ))}
                        </div>
                      ) : null}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setRaysOpen(false)} disabled={saveRayMutation.isPending}>
              Cancel
            </Button>
            <Button
              onClick={() => saveRayMutation.mutate()}
              disabled={saveRayMutation.isPending || raysLoading}
              className="gradient-primary text-primary-foreground border-0"
            >
              {saveRayMutation.isPending ? "Saving..." : "Save Ray"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={glassesOpen} onOpenChange={setGlassesOpen}>
        <DialogContent className="sm:max-w-4xl">
          <DialogHeader>
            <DialogTitle>
              {t("clinic.reservations.GlassesDistance")} {activeReservation ? `${t("clinic.reservations.ForReservation")} #${activeReservation.id}` : ""}
            </DialogTitle>
          </DialogHeader>
          {glassesLoading ? (
            <p className="text-sm text-muted-foreground">{t("clinic.reservations.LoadingGlassesRecords")}</p>
          ) : (
            <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
              <div className="grid md:grid-cols-2 gap-4">
                <div className="space-y-3 rounded-lg border p-3">
                  <p className="text-sm font-medium">{t("clinic.reservations.Distance")} (D)</p>
                  <div className="grid grid-cols-3 gap-2">
                    <Input placeholder="SPH R" value={glassesForm.SPH_R_D} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_R_D: e.target.value }))} />
                    <Input placeholder="CYL R" value={glassesForm.CYL_R_D} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_R_D: e.target.value }))} />
                    <Input placeholder="AX R" value={glassesForm.AX_R_D} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_R_D: e.target.value }))} />
                    <Input placeholder="SPH L" value={glassesForm.SPH_L_D} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_L_D: e.target.value }))} />
                    <Input placeholder="CYL L" value={glassesForm.CYL_L_D} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_L_D: e.target.value }))} />
                    <Input placeholder="AX L" value={glassesForm.AX_L_D} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_L_D: e.target.value }))} />
                  </div>
                </div>
                <div className="space-y-3 rounded-lg border p-3">
                  <p className="text-sm font-medium">Near (N)</p>
                  <div className="grid grid-cols-3 gap-2">
                    <Input placeholder="SPH R" value={glassesForm.SPH_R_N} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_R_N: e.target.value }))} />
                    <Input placeholder="CYL R" value={glassesForm.CYL_R_N} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_R_N: e.target.value }))} />
                    <Input placeholder="AX R" value={glassesForm.AX_R_N} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_R_N: e.target.value }))} />
                    <Input placeholder="SPH L" value={glassesForm.SPH_L_N} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_L_N: e.target.value }))} />
                    <Input placeholder="CYL L" value={glassesForm.CYL_L_N} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_L_N: e.target.value }))} />
                    <Input placeholder="AX L" value={glassesForm.AX_L_N} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_L_N: e.target.value }))} />
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <p className="text-sm font-medium">{t("clinic.reservations.RecentRecords")}</p>
                {glassesHistory.length === 0 ? (
                  <p className="text-sm text-muted-foreground">{t("clinic.reservations.NoGlassesRecordsYet")}</p>
                ) : (
                  <div className="space-y-2">
                    {glassesHistory.slice(0, 5).map((row) => (
                      <div key={String(row.id)} className="rounded-md border p-2 text-xs">
                        <p className="text-muted-foreground mb-1">{row.created_at ?? "—"}</p>
                        <p>D: R({row.SPH_R_D || "—"}/{row.CYL_R_D || "—"}/{row.AX_R_D || "—"}) L({row.SPH_L_D || "—"}/{row.CYL_L_D || "—"}/{row.AX_L_D || "—"})</p>
                        <p>N: R({row.SPH_R_N || "—"}/{row.CYL_R_N || "—"}/{row.AX_R_N || "—"}) L({row.SPH_L_N || "—"}/{row.CYL_L_N || "—"}/{row.AX_L_N || "—"})</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setGlassesOpen(false)} disabled={saveGlassesMutation.isPending}>
              {t("clinic.reservations.Cancel")}
            </Button>
            <Button
              onClick={() => saveGlassesMutation.mutate()}
              disabled={saveGlassesMutation.isPending || glassesLoading}
              className="gradient-primary text-primary-foreground border-0"
            >
              {saveGlassesMutation.isPending ? t("clinic.reservations.Saving") : t("clinic.reservations.SaveGlassesData")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={teethOpen} onOpenChange={setTeethOpen}>
        <DialogContent className="sm:max-w-4xl">
          <DialogHeader>
            <DialogTitle>
              {t("clinic.reservations.TeethPlan")} {activeReservation ? `${t("clinic.reservations.ForReservation")} #${activeReservation.id}` : ""}
            </DialogTitle>
          </DialogHeader>
          {teethLoading ? (
            <p className="text-sm text-muted-foreground">{t("clinic.reservations.LoadingTeethData")}</p>
          ) : (
            <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
              <div className="space-y-1">
                <Label htmlFor="teeth-general-note">{t("clinic.reservations.GeneralNotes")} (optional)</Label>
                <Textarea
                  id="teeth-general-note"
                  rows={2}
                  value={teethGeneralNote}
                  onChange={(e) => setTeethGeneralNote(e.target.value)}
                />
              </div>
              <div className="space-y-1">
                <Label htmlFor="teeth-next-plan">{t("clinic.reservations.FuturePlanForNextSession")} (optional)</Label>
                <Textarea
                  id="teeth-next-plan"
                  rows={2}
                  value={teethNextPlan}
                  onChange={(e) => setTeethNextPlan(e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label>{t("clinic.reservations.SelectTeethAndOptionalPerToothNotes")}</Label>
                <div className="rounded-lg border p-3 space-y-4">
                  <div>
                    <p className="text-xs text-muted-foreground mb-2">{t("clinic.reservations.UpperJaw")}</p>
                    <div className="grid grid-cols-8 md:grid-cols-16 gap-2">
                      {UPPER_TEETH.map((num) => {
                        const checked = selectedTeeth.includes(num);
                        return (
                          <button
                            key={num}
                            type="button"
                            className={`rounded-md border p-1 flex flex-col items-center justify-center transition-colors ${checked ? "border-primary bg-primary/10" : "hover:bg-muted"}`}
                            onClick={() =>
                              setSelectedTeeth((prev) =>
                                checked ? prev.filter((n) => n !== num) : Array.from(new Set([...prev, num])).sort((a, b) => a - b),
                              )
                            }
                            title={`Tooth #${num}`}
                          >
                            <ToothSvg selected={checked} />
                            <span className="text-[10px] font-medium">{num}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  <div>
                    <p className="text-xs text-muted-foreground mb-2">{t("clinic.reservations.LowerJaw")}</p>
                    <div className="grid grid-cols-8 md:grid-cols-16 gap-2">
                      {LOWER_TEETH.map((num) => {
                        const checked = selectedTeeth.includes(num);
                        return (
                          <button
                            key={num}
                            type="button"
                            className={`rounded-md border p-1 flex flex-col items-center justify-center transition-colors ${checked ? "border-primary bg-primary/10" : "hover:bg-muted"}`}
                            onClick={() =>
                              setSelectedTeeth((prev) =>
                                checked ? prev.filter((n) => n !== num) : Array.from(new Set([...prev, num])).sort((a, b) => a - b),
                              )
                            }
                            title={`Tooth #${num}`}
                          >
                            <ToothSvg selected={checked} />
                            <span className="text-[10px] font-medium">{num}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                </div>

                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-2">
                  {selectedTeeth.map((num) => (
                    <div key={num} className="rounded-md border p-2 space-y-1">
                      <Label htmlFor={`tooth-note-${num}`} className="text-xs">{t("clinic.reservations.ToothNote")} #{num} {t("clinic.reservations.Optional")}</Label>
                      <Input
                        id={`tooth-note-${num}`}
                        placeholder={t("clinic.reservations.WriteNote")}
                        value={toothNotes[num] ?? ""}
                        onChange={(e) => setToothNotes((prev) => ({ ...prev, [num]: e.target.value }))}
                      />
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setTeethOpen(false)} disabled={saveTeethMutation.isPending}>
              {t("clinic.reservations.Cancel")}
            </Button>
            <Button
              onClick={() => {
                if (selectedTeeth.length === 0) {
                  toast({ title: t("clinic.reservations.SelectAtLeastOneTooth"), variant: "destructive" });
                  return;
                }
                saveTeethMutation.mutate();
              }}
              disabled={saveTeethMutation.isPending || teethLoading}
              className="gradient-primary text-primary-foreground border-0"
            >
              {saveTeethMutation.isPending ? t("clinic.reservations.Saving") : t("clinic.reservations.SaveTeethData")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function toRayFormData(form: {
  date: string;
  payment: "paid" | "not_paid";
  report: string;
  cost: string;
  images: File[];
}): FormData {
  const fd = new FormData();
  fd.append("date", form.date);
  fd.append("payment", form.payment);
  fd.append("report", form.report.trim());
  if (form.cost.trim()) {
    fd.append("cost", form.cost.trim());
  }
  form.images.forEach((file) => fd.append("images[]", file));

  return fd;
}
