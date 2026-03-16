import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

type NumberRow = {
  id: string | number;
  doctor_id?: string | number;
  doctor_name?: string;
  reservation_date?: string;
  num_of_reservations?: number;
};

export default function ClinicReservationNumbers() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<NumberRow[]>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    doctor_id: "",
    reservation_date: "",
    num_of_reservations: "0",
  });
  const { toast } = useToast();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "reservation-numbers"],
    queryFn: () => clinicApi.reservationNumbers({ per_page: "500" }),
  });
  const rows = useMemo<NumberRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: NumberRow[] })?.data ?? [];
  }, [data]);
  const doctorsQuery = useQuery({
    queryKey: ["clinic", "doctors", "for-numbers"],
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
    `${r.doctor_name ?? ""} ${r.reservation_date ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ doctor_id: "", reservation_date: "", num_of_reservations: "0" });
  };

  const openShow = (row: NumberRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : doctorIdByName(row.doctor_name ?? ""),
      reservation_date: row.reservation_date ?? "",
      num_of_reservations: String(row.num_of_reservations ?? 0),
    });
  };

  const openEdit = (row: NumberRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : doctorIdByName(row.doctor_name ?? ""),
      reservation_date: row.reservation_date ?? "",
      num_of_reservations: String(row.num_of_reservations ?? 0),
    });
  };

  const onSave = () => {
    if (!form.doctor_id || !form.reservation_date) {
      toast({ title: t("clinic.reservations-numbers.missing_required_fields"), description: t("clinic.reservations-numbers.doctor_and_date_are_required"), variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      setItems((prev) => [
        {
          id: `local-${Date.now()}`,
          doctor_id: form.doctor_id,
          doctor_name: doctorNameById(form.doctor_id),
          reservation_date: form.reservation_date,
          num_of_reservations: Number(form.num_of_reservations || 0),
        },
        ...prev,
      ]);
      toast({ title: t("clinic.reservations-numbers.added"), description: t("clinic.reservations-numbers.reservation_number_row_added_in_dashboard_view") });
    } else if (dialogMode === "edit") {
      setItems((prev) =>
        prev.map((r) =>
          String(r.id) === activeId
            ? {
                ...r,
                doctor_id: form.doctor_id,
                doctor_name: doctorNameById(form.doctor_id),
                reservation_date: form.reservation_date,
                num_of_reservations: Number(form.num_of_reservations || 0),
              }
            : r,
        ),
      );
      toast({ title: t("clinic.reservations-numbers.updated"), description: t("clinic.reservations-numbers.reservation_number_row_updated_in_dashboard_view") });
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{t("clinic.reservations-numbers.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("clinic.reservations-numbers.add")}
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.reservations-numbers.search_by_doctor_or_day")}
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
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-numbers.doctor")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-numbers.date")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-numbers.reservations_count")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.reservations-numbers.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={5}>{t("clinic.reservations-numbers.loading_reservation_numbers")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={5}>
                    {error instanceof Error ? error.message : t("clinic.reservations-numbers.failed_to_load_reservation_numbers")}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={5}>{t("clinic.reservations-numbers.no_data_found")}</td>
                </tr>
              )}
              {paged.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.doctor_name ?? "---"}</td>
                  <td className="p-4 text-muted-foreground">{r.reservation_date ?? "---"}</td>
                  <td className="p-4 text-muted-foreground">{r.num_of_reservations ?? 0}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(r)}>
                        <Eye className="h-4 w-4" />
                        {t("clinic.reservations-numbers.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(r)}>
                        <Edit className="h-4 w-4" />
                        {t("clinic.reservations-numbers.edit")}
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
            <p className="text-sm text-muted-foreground">{t("clinic.reservations-numbers.page")} {safePage} {t("clinic.reservations-numbers.of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                {t("clinic.reservations-numbers.previous")}
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
              >
                {t("clinic.reservations-numbers.next")}
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>
              {dialogMode === "add" ? t("clinic.reservations-numbers.add_reservation_number") : dialogMode === "edit" ? t("clinic.reservations-numbers.edit_reservation_number") : t("clinic.reservations-numbers.reservation_number_details")}
            </DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>{t("clinic.reservations-numbers.doctor")}</Label>
              <Select
                value={form.doctor_id}
                onValueChange={(v) => setForm((prev) => ({ ...prev, doctor_id: v }))}
                disabled={dialogMode === "show"}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t("clinic.reservations-numbers.select_doctor")} />
                </SelectTrigger>
                <SelectContent>
                  {doctors.map((d) => (
                    <SelectItem key={String(d.id)} value={String(d.id)}>
                      {d.name ?? `${t("clinic.reservations-numbers.doctor")} ${d.id}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.reservations-numbers.date")}</Label>
                <Input
                  type="date"
                  value={form.reservation_date}
                  onChange={(e) => setForm((prev) => ({ ...prev, reservation_date: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.reservations-numbers.reservations_count")}</Label>
                <Input
                  type="number"
                  value={form.num_of_reservations}
                  onChange={(e) => setForm((prev) => ({ ...prev, num_of_reservations: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.reservations-numbers.close")}</Button>
            {dialogMode !== "show" && <Button onClick={onSave}>{t("clinic.reservations-numbers.save")}</Button>}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
