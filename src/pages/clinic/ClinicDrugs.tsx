import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

type DrugRow = {
  id: string | number;
  clinic_id?: number;
  doctor_id?: number;
  doctor_name?: string | null;
  name?: string;
  type?: string;
  dose?: string;
  frequency?: string;
  period?: string;
  notes?: string | null;
  created_at?: string;
};

type DoctorOption = {
  id: number | string;
  name?: string;
};

export default function ClinicDrugs() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const perPage = 10;
  const { t } = useLanguage();
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    doctor_id: "",
    name: "",
    type: "",
    dose: "",
    frequency: "",
    period: "",
    notes: "",
  });

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "drugs"],
    queryFn: () => clinicApi.drugs(),
  });

  const { data: doctorsData } = useQuery({
    queryKey: ["clinic", "doctors", "drugs-page"],
    queryFn: () => clinicApi.doctors(),
  });

  const rows = useMemo<DrugRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: DrugRow[] })?.data ?? [];
  }, [data]);

  const doctors = useMemo<DoctorOption[]>(() => {
    const root = (doctorsData as { data?: unknown })?.data ?? doctorsData;
    return (Array.isArray(root) ? root : []) as DoctorOption[];
  }, [doctorsData]);

  const filtered = rows.filter((row) =>
    `${row.name ?? ""} ${row.type ?? ""} ${row.dose ?? ""} ${row.doctor_name ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const createMutation = useMutation({
    mutationFn: (payload: { doctor_id: number; name: string; type: string; dose: string; frequency: string; period: string; notes?: string | null }) =>
      clinicApi.createDrug(payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "drugs"] });
      toast({ title: "Drug created" });
      setDialogMode(null);
    },
    onError: (e) => {
      toast({ title: e instanceof Error ? e.message : "Failed to create drug", variant: "destructive" });
    },
  });

  const updateMutation = useMutation({
    mutationFn: (args: { id: string; payload: { doctor_id: number; name: string; type: string; dose: string; frequency: string; period: string; notes?: string | null } }) =>
      clinicApi.updateDrug(args.id, args.payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "drugs"] });
      toast({ title: "Drug updated" });
      setDialogMode(null);
    },
    onError: (e) => {
      toast({ title: e instanceof Error ? e.message : "Failed to update drug", variant: "destructive" });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string) => clinicApi.deleteDrug(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "drugs"] });
      toast({ title: "Drug deleted" });
    },
    onError: (e) => {
      toast({ title: e instanceof Error ? e.message : "Failed to delete drug", variant: "destructive" });
    },
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ doctor_id: "", name: "", type: "", dose: "", frequency: "", period: "", notes: "" });
  };

  const openShow = (row: DrugRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : "",
      name: row.name ?? "",
      type: row.type ?? "",
      dose: row.dose ?? "",
      frequency: row.frequency ?? "",
      period: row.period ?? "",
      notes: row.notes ?? "",
    });
  };

  const openEdit = (row: DrugRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : "",
      name: row.name ?? "",
      type: row.type ?? "",
      dose: row.dose ?? "",
      frequency: row.frequency ?? "",
      period: row.period ?? "",
      notes: row.notes ?? "",
    });
  };

  const onSave = () => {
    if (!form.doctor_id || !form.name.trim() || !form.type.trim() || !form.dose.trim() || !form.frequency.trim() || !form.period.trim()) {
      toast({
        title: "Missing required fields",
        description: "Doctor, name, type, dose, frequency and period are required.",
        variant: "destructive",
      });
      return;
    }

    const payload = {
      doctor_id: Number(form.doctor_id),
      name: form.name.trim(),
      type: form.type.trim(),
      dose: form.dose.trim(),
      frequency: form.frequency.trim(),
      period: form.period.trim(),
      notes: form.notes.trim() || null,
    };

    if (dialogMode === "add") {
      createMutation.mutate(payload);
      return;
    }
    if (dialogMode === "edit") {
      updateMutation.mutate({ id: activeId, payload });
    }
  };

  const onDelete = (row: DrugRow) => {
    const ok = window.confirm(`Delete drug "${row.name ?? row.id}"?`);
    if (!ok) return;
    deleteMutation.mutate(String(row.id));
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{t("clinic.drugs.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("clinic.drugs.add")}
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.drugs.search")}
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
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.type")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.dose")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.frequency")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.period")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.doctor")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.drugs.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={8}>{t("clinic.drugs.loading")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={8}>
                    {error instanceof Error ? error.message : t("clinic.drugs.failed_to_load")}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={8}>{t("clinic.drugs.no_drugs_found")}</td>
                </tr>
              )}
              {paged.map((row) => (
                <tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(row.id)}</td>
                  <td className="p-4 font-medium">{row.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{row.type ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{row.dose ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{row.frequency ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{row.period ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{row.doctor_name ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(row)}>
                        <Eye className="h-4 w-4" />
                        {t("clinic.drugs.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(row)}>
                        <Edit className="h-4 w-4" />
                        {t("clinic.drugs.edit")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => onDelete(row)} disabled={deleteMutation.isPending}>
                        <Trash2 className="h-4 w-4" />
                        {t("clinic.drugs.delete")}
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
            <p className="text-sm text-muted-foreground">{t("clinic.drugs.page")} {safePage} {t("clinic.drugs.of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                {t("clinic.drugs.previous")}
              </Button>
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={safePage >= totalPages}>
                {t("clinic.drugs.next")}
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>
              {dialogMode === "add" ? t("clinic.drugs.add") : dialogMode === "edit" ? t("clinic.drugs.edit") : t("clinic.drugs.details")}
            </DialogTitle>
          </DialogHeader>

          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>{t("clinic.drugs.doctor")}</Label>
              <select
                title="Drug doctor"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.doctor_id}
                onChange={(e) => setForm((prev) => ({ ...prev, doctor_id: e.target.value }))}
                disabled={dialogMode === "show"}
              >
                <option value="">{t("clinic.drugs.select_doctor")}</option>
                {doctors.map((doctor) => (
                  <option key={String(doctor.id)} value={String(doctor.id)}>
                    {doctor.name ?? `Doctor ${doctor.id}`}
                  </option>
                ))}
              </select>
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.drugs.name")}</Label>
                <Input value={form.name} onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.drugs.type")}</Label>
                <Input value={form.type} onChange={(e) => setForm((prev) => ({ ...prev, type: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>

            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.drugs.dose")}</Label>
                <Input value={form.dose} onChange={(e) => setForm((prev) => ({ ...prev, dose: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.drugs.frequency")}</Label>
                <Input value={form.frequency} onChange={(e) => setForm((prev) => ({ ...prev, frequency: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.drugs.period")}</Label>
                <Input value={form.period} onChange={(e) => setForm((prev) => ({ ...prev, period: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>

            <div className="space-y-2">
              <Label>{t("clinic.drugs.notes")}</Label>
              <Textarea rows={3} value={form.notes} onChange={(e) => setForm((prev) => ({ ...prev, notes: e.target.value }))} disabled={dialogMode === "show"} />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.drugs.close")}</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending} className="gradient-primary text-primary-foreground border-0">
                {createMutation.isPending || updateMutation.isPending ? t("clinic.drugs.saving") : t("clinic.drugs.save")}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

