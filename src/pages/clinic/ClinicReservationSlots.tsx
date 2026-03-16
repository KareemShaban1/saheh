import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Link } from "react-router-dom";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

type SlotRow = {
  id: string | number;
  doctor_id?: string | number;
  doctor_name?: string;
  date?: string;
  start_time?: string;
  end_time?: string;
  duration?: number;
  total_reservations?: number;
};

export default function ClinicReservationSlots() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<SlotRow[]>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    doctor_id: "",
    date: "",
    start_time: "",
    end_time: "",
    duration: "30",
  });
  const { toast } = useToast();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "reservation-slots"],
    queryFn: () => clinicApi.reservationSlots({ per_page: "500" }),
  });
  const rows = useMemo<SlotRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: SlotRow[] })?.data ?? [];
  }, [data]);
  const doctorsQuery = useQuery({
    queryKey: ["clinic", "doctors", "for-slots"],
    queryFn: () => clinicApi.doctors(),
  });
  const doctors = useMemo<Array<{ id: string | number; name?: string }>>(() => {
    const root = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
    return Array.isArray(root) ? (root as Array<{ id: string | number; name?: string }>) : [];
  }, [doctorsQuery.data]);
  const doctorNameById = (doctorId: string) =>
    doctors.find((d) => String(d.id) === doctorId)?.name ?? `Doctor ${doctorId}`;
  const doctorIdByName = (doctorName: string) =>
    String(doctors.find((d) => (d.name ?? "").toLowerCase() === doctorName.toLowerCase())?.id ?? "");

  useEffect(() => {
    setItems(rows);
  }, [rows]);

  const filtered = items.filter((r) =>
    `${r.doctor_name ?? ""} ${r.date ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ doctor_id: "", date: "", start_time: "", end_time: "", duration: "30" });
  };

  const openShow = (row: SlotRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : doctorIdByName(row.doctor_name ?? ""),
      date: row.date ?? "",
      start_time: row.start_time ?? "",
      end_time: row.end_time ?? "",
      duration: String(row.duration ?? 30),
    });
  };

  const openEdit = (row: SlotRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : doctorIdByName(row.doctor_name ?? ""),
      date: row.date ?? "",
      start_time: row.start_time ?? "",
      end_time: row.end_time ?? "",
      duration: String(row.duration ?? 30),
    });
  };

  const onSave = () => {
    if (!form.doctor_id || !form.date) {
      toast({ title: t("clinic.reservations-slots.missing_required_fields"), description: t("clinic.reservations-slots.doctor_and_date_are_required"), variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      setItems((prev) => [
        {
          id: `local-${Date.now()}`,
          doctor_id: form.doctor_id,
          doctor_name: doctorNameById(form.doctor_id),
          date: form.date,
          start_time: form.start_time,
          end_time: form.end_time,
          duration: Number(form.duration || 0),
        },
        ...prev,
      ]);
      toast({ title: t("clinic.reservations-slots.added"), description: t("clinic.reservations-slots.slot_row_added_in_dashboard_view") });
    } else if (dialogMode === "edit") {
      setItems((prev) =>
        prev.map((r) =>
          String(r.id) === activeId
            ? {
                ...r,
                doctor_id: form.doctor_id,
                doctor_name: doctorNameById(form.doctor_id),
                date: form.date,
                start_time: form.start_time,
                end_time: form.end_time,
                duration: Number(form.duration || 0),
              }
            : r,
        ),
      );
      toast({ title: t("clinic.reservations-slots.updated"), description: t("clinic.reservations-slots.slot_row_updated_in_dashboard_view") });
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{t("clinic.reservations-slots.title")}</h2>
        </div>
        <Button asChild variant="outline" className="gradient-primary text-primary-foreground border-0 gap-2">
          <Link to="/clinic-dashboard/slots/new">{t("clinic.reservations-slots.add")}</Link>
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.reservations-slots.search_by_doctor_or_day")}
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-10"
        />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-slots.doctor")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-slots.date")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-slots.start")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-slots.end")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-slots.duration_min")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-slots.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={7}>{t("clinic.reservations-slots.loading_reservation_slots")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={7}>
                    {error instanceof Error ? error.message : t("clinic.reservations-slots.failed_to_load_reservation_slots")}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={7}>{t("clinic.reservations-slots.no_data_found")}</td>
                </tr>
              )}
              {paged.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.doctor_name ?? "---"}</td>
                  <td className="p-4 text-muted-foreground">{r.date ?? "---"}</td>
                  <td className="p-4 text-muted-foreground">{r.start_time ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.end_time ?? "---"}</td>
                  <td className="p-4 text-muted-foreground">{r.duration ?? 0}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(r)}>
                        <Eye className="h-4 w-4" />
                        {t("clinic.reservations-slots.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(r)}>
                        <Edit className="h-4 w-4" />
                        {t("clinic.reservations-slots.edit")}
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
            <p className="text-sm text-muted-foreground">{t("clinic.reservations-slots.page")} {safePage} {t("clinic.reservations-slots.of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                {t("clinic.reservations-slots.previous")}
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
              >
                {t("clinic.reservations-slots.next")}
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>{dialogMode === "add" ? t("clinic.reservations-slots.add_slot") : dialogMode === "edit" ? t("clinic.reservations-slots.edit_slot") : t("clinic.reservations-slots.slot_details")}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>{t("clinic.reservations-slots.doctor")}</Label>
              <Select
                value={form.doctor_id}
                onValueChange={(v) => setForm((prev) => ({ ...prev, doctor_id: v }))}
                disabled={dialogMode === "show"}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t("clinic.reservations-slots.select_doctor")} />
                </SelectTrigger>
                <SelectContent>
                  {doctors.map((d) => (
                    <SelectItem key={String(d.id)} value={String(d.id)}>
                      {d.name ?? `${t("clinic.reservations-slots.doctor")} ${d.id}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.reservations-slots.date")}</Label>
                <Input
                  type="date"
                  value={form.date}
                  onChange={(e) => setForm((prev) => ({ ...prev, date: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.reservations-slots.start")}</Label>
                <Input
                  type="time"
                  value={form.start_time}
                  onChange={(e) => setForm((prev) => ({ ...prev, start_time: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.reservations-slots.end")}</Label>
                <Input
                  type="time"
                  value={form.end_time}
                  onChange={(e) => setForm((prev) => ({ ...prev, end_time: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>{t("clinic.reservations-slots.duration_min")}</Label>
              <Input
                type="number"
                value={form.duration}
                onChange={(e) => setForm((prev) => ({ ...prev, duration: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.reservations-slots.close")}</Button>
            {dialogMode !== "show" && <Button onClick={onSave}>{t("clinic.reservations-slots.save")}</Button>}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
