import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, FileText, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

type ServiceRow = {
  id: string | number;
  name?: string;
  category?: "main" | "sub" | string;
  price?: string;
  doctor_id?: number | null;
  doctor_name?: string | null;
  notes?: string | null;
  status?: string;
  service_instructions?: {
    id?: number;
    instructions?: string | null;
    type?: "pre" | "post" | string;
    notes?: string | null;
  }[];
  service_instructions_count?: number;
};

type DoctorOption = {
  id: number;
  name?: string;
};

type ServiceInstructionForm = {
  instructions: string;
  type: "pre" | "post";
  notes: string;
};

type InstructionModalService = {
  id: string;
  service_name: string;
  doctor_id: number | null;
  type: "main" | "sub";
  price: number;
  notes: string | null;
};

export default function ClinicServices() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [isInstructionModalOpen, setIsInstructionModalOpen] = useState(false);
  const [instructionService, setInstructionService] = useState<InstructionModalService | null>(null);
  const [instructionForm, setInstructionForm] = useState<ServiceInstructionForm[]>([]);
  const [form, setForm] = useState({
    service_name: "",
    doctor_id: "",
    type: "sub" as "main" | "sub",
    price: "0",
    notes: "",
  });
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "services"],
    queryFn: () => clinicApi.services(),
  });

  const { data: doctorsData } = useQuery({
    queryKey: ["clinic", "doctors"],
    queryFn: () => clinicApi.doctors(),
  });

  const services = useMemo<ServiceRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: ServiceRow[] })?.data ?? [];
  }, [data]);

  const doctors = useMemo<DoctorOption[]>(() => {
    const root = (doctorsData as { data?: unknown })?.data ?? doctorsData;
    return ((root as DoctorOption[]) ?? []).map((doctor) => ({
      id: doctor.id,
      name: doctor.name ?? "Unnamed doctor",
    }));
  }, [doctorsData]);

  const createMutation = useMutation({
    mutationFn: (payload: { service_name: string; doctor_id?: number | null; type: "main" | "sub"; price: number; notes?: string | null; service_instructions?: ServiceInstructionForm[] }) =>
      clinicApi.createService(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["clinic", "services"] });
      toast({ title: t("clinic.services.service_added") });
      setDialogMode(null);
    },
    onError: (err: unknown) => {
      toast({ title: err instanceof Error ? err.message : "Failed to add service", variant: "destructive" });
    },
  });

  const updateMutation = useMutation({
    mutationFn: (args: { id: string; payload: { service_name: string; doctor_id?: number | null; type: "main" | "sub"; price: number; notes?: string | null; service_instructions?: ServiceInstructionForm[] } }) =>
      clinicApi.updateService(args.id, args.payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["clinic", "services"] });
      toast({ title: t("clinic.services.service_updated") });
      setDialogMode(null);
    },
    onError: (err: unknown) => {
      toast({ title: err instanceof Error ? err.message : "Failed to update service", variant: "destructive" });
    },
  });

  const updateInstructionsMutation = useMutation({
    mutationFn: (args: { id: string; payload: { service_name: string; doctor_id?: number | null; type: "main" | "sub"; price: number; notes?: string | null; service_instructions?: ServiceInstructionForm[] } }) =>
      clinicApi.updateService(args.id, args.payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["clinic", "services"] });
      toast({ title: "Service instructions updated" });
      setIsInstructionModalOpen(false);
      setInstructionService(null);
      setInstructionForm([]);
    },
    onError: (err: unknown) => {
      toast({ title: err instanceof Error ? err.message : "Failed to update service instructions", variant: "destructive" });
    },
  });

  const filtered = services.filter((s) =>
    `${s.name ?? ""} ${s.category ?? ""} ${s.doctor_name ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ service_name: "", doctor_id: "", type: "sub", price: "0", notes: "" });
  };
  const openShow = (s: ServiceRow) => {
    setDialogMode("show");
    setActiveId(String(s.id));
    setForm({
      service_name: s.name ?? "",
      doctor_id: s.doctor_id ? String(s.doctor_id) : "",
      type: s.category === "main" ? "main" : "sub",
      price: s.price ?? "0",
      notes: s.notes ?? "",
    });
  };
  const openEdit = (s: ServiceRow) => {
    setDialogMode("edit");
    setActiveId(String(s.id));
    setForm({
      service_name: s.name ?? "",
      doctor_id: s.doctor_id ? String(s.doctor_id) : "",
      type: s.category === "main" ? "main" : "sub",
      price: s.price ?? "0",
      notes: s.notes ?? "",
    });
  };

  const openInstructionModal = (s: ServiceRow) => {
    setInstructionService({
      id: String(s.id),
      service_name: s.name ?? "",
      doctor_id: s.doctor_id ?? null,
      type: s.category === "main" ? "main" : "sub",
      price: Number(s.price ?? "0"),
      notes: s.notes ?? null,
    });
    setInstructionForm(
      (s.service_instructions ?? []).map((item) => ({
        instructions: item.instructions ?? "",
        type: item.type === "post" ? "post" : "pre",
        notes: item.notes ?? "",
      })),
    );
    setIsInstructionModalOpen(true);
  };

  const saveInstructions = () => {
    if (!instructionService) return;
    updateInstructionsMutation.mutate({
      id: instructionService.id,
      payload: {
        service_name: instructionService.service_name,
        doctor_id: instructionService.doctor_id,
        type: instructionService.type,
        price: instructionService.price,
        notes: instructionService.notes,
        service_instructions: instructionForm
          .map((item) => ({
            instructions: item.instructions.trim(),
            type: item.type,
            notes: item.notes.trim() || null,
          }))
          .filter((item) => item.instructions.length > 0),
      },
    });
  };

  const onSave = () => {
    if (!form.service_name.trim()) {
      toast({ title: t("clinic.services.service_name_is_required"), variant: "destructive" });
      return;
    }

    const parsedPrice = Number(form.price);
    if (Number.isNaN(parsedPrice) || parsedPrice < 0) {
      toast({ title: t("clinic.services.price_must_be_a_valid_non_negative_number"), variant: "destructive" });
      return;
    }

    const payload = {
      service_name: form.service_name.trim(),
      doctor_id: form.doctor_id ? Number(form.doctor_id) : null,
      type: form.type,
      price: parsedPrice,
      notes: form.notes.trim() || null,
    };

    if (dialogMode === "add") {
      createMutation.mutate(payload);
    } else if (dialogMode === "edit") {
      updateMutation.mutate({ id: activeId, payload });
    }
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{t("clinic.services.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2"><Plus className="h-4 w-4" />{t("clinic.services.add")}</Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.services.search_by_name_type_or_doctor")}
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          className="pl-10"
        />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.services.name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.services.type")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.services.doctor")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.services.price")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Instructions</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.services.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={7}>{t("clinic.services.loading_services")}</td></tr>}
              {error && <tr><td className="p-4 text-destructive" colSpan={7}>{error instanceof Error ? error.message : t("clinic.services.failed_to_load_services")}</td></tr>}
              {!isLoading && !error && paged.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={7}>{t("clinic.services.no_services_found")}</td></tr>}
              {paged.map((s) => (
                <tr key={String(s.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(s.id)}</td>
                  <td className="p-4 font-medium">{s.name ?? "---"}</td>
                  <td className="p-4 text-muted-foreground capitalize">{s.category ?? "sub"}</td>
                  <td className="p-4 text-muted-foreground">{s.doctor_name ?? "---"}</td>
                  <td className="p-4 text-muted-foreground">{s.price ?? "0"} EGP</td>
                  <td className="p-4 text-muted-foreground">{s.service_instructions_count ?? s.service_instructions?.length ?? 0}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openInstructionModal(s)}>
                        <FileText className="h-4 w-4" />
                        Instructions
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(s)}><Eye className="h-4 w-4" />{t("clinic.services.show")}</Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(s)}><Edit className="h-4 w-4" />{t("clinic.services.edit")}</Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">{t("clinic.services.page")} {safePage} {t("clinic.services.of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>{t("clinic.services.previous")}</Button>
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={safePage >= totalPages}>{t("clinic.services.next")}</Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>{dialogMode === "add" ? t("clinic.services.add") : dialogMode === "edit" ? t("clinic.services.edit") : t("clinic.services.details")}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>{t("clinic.services.name")}</Label>
              <Input
                value={form.service_name}
                onChange={(e) => setForm((f) => ({ ...f, service_name: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label htmlFor="service-doctor">{t("clinic.services.doctor")}</Label>
                <select
                  id="service-doctor"
                  title={t("clinic.services.doctor")}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.doctor_id}
                  onChange={(e) => setForm((f) => ({ ...f, doctor_id: e.target.value }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="">{t("clinic.services.select_doctor")}</option>
                  {doctors.map((doctor) => (
                    <option key={doctor.id} value={doctor.id}>
                      {doctor.name ?? `${t("clinic.services.doctor")} ${doctor.id}`}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="service-type">{t("clinic.services.type")}</Label>
                <select
                  id="service-type"
                  title={t("clinic.services.type")}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value as "main" | "sub" }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="main">{t("clinic.services.main")}</option>
                  <option value="sub">{t("clinic.services.sub")}</option>
                </select>
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.services.price")}</Label>
                <Input
                  type="number"
                  min="0"
                  step="0.01"
                  value={form.price}
                  onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>{t("clinic.services.notes")}</Label>
              <Textarea
                value={form.notes}
                onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
                disabled={dialogMode === "show"}
                rows={4}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>
              {t("clinic.services.close")}
            </Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? "Saving..." : "Save"}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={isInstructionModalOpen} onOpenChange={(open) => !open && setIsInstructionModalOpen(false)}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Service Instructions {instructionService?.service_name ? `- ${instructionService.service_name}` : ""}</DialogTitle>
          </DialogHeader>
          <div className="space-y-3 py-2 max-h-[70vh] overflow-y-auto">
            <div className="flex items-center justify-between">
              <Label>Instructions</Label>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setInstructionForm((prev) => [...prev, { instructions: "", type: "pre", notes: "" }])}
              >
                Add Instruction
              </Button>
            </div>
            {instructionForm.length === 0 && (
              <p className="text-sm text-muted-foreground">No instructions found for this service.</p>
            )}
            {instructionForm.map((item, index) => (
              <div key={`instructions-${index}`} className="rounded-lg border p-3 space-y-2">
                <div className="grid sm:grid-cols-3 gap-2">
                  <div className="sm:col-span-2 space-y-1">
                    <Label>Instructions</Label>
                    <Textarea
                      value={item.instructions}
                      onChange={(e) =>
                        setInstructionForm((prev) =>
                          prev.map((entry, i) => (i === index ? { ...entry, instructions: e.target.value } : entry)),
                        )
                      }
                      rows={2}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label>Type</Label>
                    <select
                      title="Instructions type"
                      className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                      value={item.type}
                      onChange={(e) =>
                        setInstructionForm((prev) =>
                          prev.map((entry, i) => (i === index ? { ...entry, type: e.target.value as "pre" | "post" } : entry)),
                        )
                      }
                    >
                      <option value="pre">Before the service</option>
                      <option value="post">After the service</option>
                    </select>
                  </div>
                </div>
                <div className="space-y-1">
                  <Label>Notes</Label>
                  <Textarea
                    value={item.notes}
                    onChange={(e) =>
                      setInstructionForm((prev) =>
                        prev.map((entry, i) => (i === index ? { ...entry, notes: e.target.value } : entry)),
                      )
                    }
                    rows={2}
                  />
                </div>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setInstructionForm((prev) => prev.filter((_, i) => i !== index))}
                >
                  Delete
                </Button>
              </div>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsInstructionModalOpen(false)}>
              {t("clinic.services.close")}
            </Button>
            <Button onClick={saveInstructions} disabled={updateInstructionsMutation.isPending || !instructionService}>
              {updateInstructionsMutation.isPending ? "Saving..." : t("clinic.services.save_instructions")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

