import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Plus, Search, Eye, Edit, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { labApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

type AnalysisRow = {
  id: string | number;
  patient_id?: number;
  patient_name?: string;
  reservation_id?: number | null;
  date?: string;
  doctor_name?: string | null;
  payment?: "paid" | "not_paid";
  cost?: string;
  report?: string | null;
  services_count?: number;
};

type PatientRow = {
  id: string | number;
  name?: string;
};

type ServiceRow = {
  id: string | number;
  name?: string;
  price?: string;
  unit?: string;
  normal_range?: string;
};

type AnalysisServiceRow = {
  option_id?: string;
  lab_service_id: string;
  value: string;
  images: File[];
  existing_images?: string[];
};

type LabMedicalAnalysesProps = {
  todayOnly?: boolean;
};

export default function LabMedicalAnalyses({ todayOnly = false }: LabMedicalAnalysesProps) {
  const { t } = useLanguage();
  const today = new Date().toISOString().slice(0, 10);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [filterDate, setFilterDate] = useState(todayOnly ? today : "");
  const [filterPayment, setFilterPayment] = useState<"all" | "paid" | "not_paid">("all");
  const [filterDoctor, setFilterDoctor] = useState("");
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState("");
  const [deleteId, setDeleteId] = useState("");
  const [form, setForm] = useState({
    patient_id: "",
    reservation_id: "",
    date: "",
    doctor_name: "",
    payment: "not_paid" as "paid" | "not_paid",
    report: "",
    services: [] as AnalysisServiceRow[],
  });
  const perPage = 10;
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const effectiveDate = todayOnly ? today : filterDate;

  const analysesQuery = useQuery({
    queryKey: ["lab", "medical-analyses", page, perPage, search, effectiveDate, filterPayment, filterDoctor, todayOnly],
    queryFn: () =>
      labApi.medicalAnalyses({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
        ...(effectiveDate ? { date: effectiveDate } : {}),
        ...(filterPayment !== "all" ? { payment: filterPayment } : {}),
        ...(filterDoctor.trim() ? { doctor_name: filterDoctor.trim() } : {}),
      }),
  });

  const patientsQuery = useQuery({
    queryKey: ["lab", "patients", "options"],
    queryFn: () => labApi.patients({ per_page: "200" }),
  });

  const servicesQuery = useQuery({
    queryKey: ["lab", "services", "options"],
    queryFn: () => labApi.services({ per_page: "300" }),
  });

  const root = (analysesQuery.data as { data?: unknown })?.data ?? analysesQuery.data;
  const analyses = ((root as { data?: AnalysisRow[] })?.data ?? []) as AnalysisRow[];
  const pagination = (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;

  const patientsRoot = (patientsQuery.data as { data?: unknown })?.data ?? patientsQuery.data;
  const patients = ((patientsRoot as { data?: PatientRow[] })?.data ?? []) as PatientRow[];

  const servicesRoot = (servicesQuery.data as { data?: unknown })?.data ?? servicesQuery.data;
  const services = ((servicesRoot as { data?: ServiceRow[] })?.data ?? []) as ServiceRow[];

  const serviceMap = useMemo(() => {
    const map = new Map<string, ServiceRow>();
    for (const s of services) map.set(String(s.id), s);
    return map;
  }, [services]);

  const createMutation = useMutation({
    mutationFn: () => labApi.createMedicalAnalysis(toAnalysisFormData(form)),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "medical-analyses"] });
      toast({ title: t("lab.medical_analyses.medical_analysis_created") });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: t("lab.medical_analyses.failed_to_create_analysis"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () => labApi.updateMedicalAnalysis(activeId, toAnalysisFormData(form)),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "medical-analyses"] });
      toast({ title: t("lab.medical_analyses.medical_analysis_updated") });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: t("lab.medical_analyses.failed_to_update_analysis"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => labApi.deleteMedicalAnalysis(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "medical-analyses"] });
      toast({ title: t("lab.medical_analyses.medical_analysis_deleted") });
      setDeleteId("");
    },
    onError: (e) =>
      toast({
        title: t("lab.medical_analyses.failed_to_delete_analysis"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const resetForm = () => {
    setForm({
      patient_id: "",
      reservation_id: "",
      date: "",
      doctor_name: "",
      payment: "not_paid",
      report: "",
      services: [],
    });
  };

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    resetForm();
  };

  const hydrateFromDetails = (
    item: AnalysisRow & {
      services?: Array<{ option_id?: number | string; lab_service_id?: number | string; value?: string | null; images?: string[] }>;
    },
  ) => {
    setForm({
      patient_id: item.patient_id ? String(item.patient_id) : "",
      reservation_id: item.reservation_id ? String(item.reservation_id) : "",
      date: item.date ?? "",
      doctor_name: item.doctor_name ?? "",
      payment: item.payment ?? "not_paid",
      report: item.report ?? "",
      services: (item.services ?? []).map((s) => ({
        option_id: s.option_id ? String(s.option_id) : undefined,
        lab_service_id: s.lab_service_id ? String(s.lab_service_id) : "",
        value: s.value ?? "",
        images: [],
        existing_images: s.images ?? [],
      })),
    });
  };

  const openShow = async (row: AnalysisRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    const details = await labApi.medicalAnalysis(row.id);
    const detailsRoot = (details as { data?: unknown })?.data ?? details;
    hydrateFromDetails(detailsRoot as AnalysisRow & { services?: Array<{ option_id?: number | string; lab_service_id?: number | string; value?: string | null; images?: string[] }> });
  };

  const openEdit = async (row: AnalysisRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    const details = await labApi.medicalAnalysis(row.id);
    const detailsRoot = (details as { data?: unknown })?.data ?? details;
    hydrateFromDetails(detailsRoot as AnalysisRow & { services?: Array<{ option_id?: number | string; lab_service_id?: number | string; value?: string | null; images?: string[] }> });
  };

  const addServiceRow = () => {
    setForm((prev) => ({
      ...prev,
      services: [...prev.services, { lab_service_id: "", value: "", images: [], existing_images: [] }],
    }));
  };

  const updateServiceRow = (index: number, patch: Partial<AnalysisServiceRow>) => {
    setForm((prev) => ({
      ...prev,
      services: prev.services.map((row, i) => (i === index ? { ...row, ...patch } : row)),
    }));
  };

  const removeServiceRow = (index: number) => {
    setForm((prev) => ({
      ...prev,
      services: prev.services.filter((_, i) => i !== index),
    }));
  };

  const onSave = () => {
    if (!form.patient_id || !form.date) {
      toast({ title: t("lab.medical_analyses.patient_and_date_required"), variant: "destructive" });
      return;
    }
    if (dialogMode === "add") createMutation.mutate();
    if (dialogMode === "edit") updateMutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{todayOnly ? t("lab.medical_analyses.today_title") : t("lab.medical_analyses.title")}</h2>
          <p className="text-muted-foreground text-sm mt-1">{todayOnly ? t("lab.medical_analyses.today_description") : t("lab.medical_analyses.description")}</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("lab.medical_analyses.add")}
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("lab.medical_analyses.search")}
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-10"
        />
      </div>

      <div className="grid sm:grid-cols-4 gap-3 mb-4">
        <Input
          type="date"
          value={effectiveDate}
          onChange={(e) => {
            if (!todayOnly) {
              setFilterDate(e.target.value);
              setPage(1);
            }
          }}
          disabled={todayOnly}
        />
        <Select
          value={filterPayment}
          onValueChange={(value: "all" | "paid" | "not_paid") => {
            setFilterPayment(value);
            setPage(1);
          }}
        >
          <SelectTrigger><SelectValue placeholder={t("lab.medical_analyses.payment")} /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t("lab.medical_analyses.all_payments")}</SelectItem>
            <SelectItem value="paid">{t("lab.medical_analyses.paid")}</SelectItem>
            <SelectItem value="not_paid">{t("lab.medical_analyses.not_paid")}</SelectItem>
          </SelectContent>
        </Select>
        <Input
          placeholder={t("lab.medical_analyses.filter_doctor")}
          value={filterDoctor}
          onChange={(e) => {
            setFilterDoctor(e.target.value);
            setPage(1);
          }}
        />
        <Button
          variant="outline"
          className="border-0 gap-2"
	variant="destructive"
          onClick={() => {
            if (!todayOnly) setFilterDate("");
            setFilterPayment("all");
            setFilterDoctor("");
            setPage(1);
          }}
        >
          {t("lab.medical_analyses.clear_filters")}
        </Button>
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.patient")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.date")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.doctor")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.payment")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.cost")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.services")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.medical_analyses.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {analysesQuery.isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>{t("lab.medical_analyses.loading_analyses")}</td></tr>
              )}
              {analysesQuery.error && (
                <tr><td className="p-4 text-destructive" colSpan={8}>{analysesQuery.error instanceof Error ? analysesQuery.error.message : t("lab.medical_analyses.failed_to_load_analyses")}</td></tr>
              )}
              {!analysesQuery.isLoading && !analysesQuery.error && analyses.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>{t("lab.medical_analyses.no_analyses_found")}</td></tr>
              )}
              {!analysesQuery.isLoading && !analysesQuery.error && analyses.map((a) => (
                <tr key={String(a.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(a.id)}</td>
                  <td className="p-4 font-medium">{a.patient_name ?? t("lab.medical_analyses.unknown")}</td>
                  <td className="p-4 text-muted-foreground">{a.date ?? t("lab.medical_analyses.unknown")}</td>
                  <td className="p-4 text-muted-foreground">{a.doctor_name ?? t("lab.medical_analyses.unknown")}</td>
                  <td className="p-4"><Badge variant={a.payment === "paid" ? "default" : "secondary"}>{a.payment ?? "not_paid"}</Badge></td>
                  <td className="p-4 text-muted-foreground">{a.cost ?? "0"} EGP</td>
                  <td className="p-4 text-muted-foreground">{a.services_count ?? 0}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(a)}>
                        <Eye className="h-4 w-4" />{t("lab.medical_analyses.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(a)}>
                        <Edit className="h-4 w-4" />{t("lab.medical_analyses.edit")}
                      </Button>
                      <Button variant="destructive" size="sm" className="gap-2" onClick={() => setDeleteId(String(a.id))}>
                        <Trash2 className="h-4 w-4" />{t("lab.medical_analyses.delete")}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="flex items-center justify-between p-4 border-t">
          <p className="text-sm text-muted-foreground">
            {t("lab.medical_analyses.page")} {pagination?.current_page ?? page} {t("lab.medical_analyses.of")} {pagination?.last_page ?? 1}
            {typeof pagination?.total === "number" ? ` (${pagination.total} total)` : ""}
          </p>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={(pagination?.current_page ?? page) <= 1}>Previous</Button>
            <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(pagination?.last_page ?? p, p + 1))} disabled={(pagination?.current_page ?? page) >= (pagination?.last_page ?? 1)}>Next</Button>
          </div>
        </div>
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-4xl max-h-[85vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{dialogMode === "add" ? t("lab.medical_analyses.add") : dialogMode === "edit" ? t("lab.medical_analyses.edit") : t("lab.medical_analyses.details")}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-4 gap-4">
              <div className="space-y-2 sm:col-span-2">
                <Label>{t("lab.medical_analyses.patient")}</Label>
                <Select value={form.patient_id} onValueChange={(value) => setForm((f) => ({ ...f, patient_id: value }))} disabled={dialogMode === "show"}>
                  <SelectTrigger><SelectValue placeholder={t("lab.medical_analyses.select_patient")} /></SelectTrigger>
                  <SelectContent>
                    {patients.map((p) => (
                      <SelectItem key={String(p.id)} value={String(p.id)}>
                        {p.name ?? `Patient ${p.id}`}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>{t("lab.medical_analyses.date")}</Label>
                <Input type="date" value={form.date} onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("lab.medical_analyses.payment")}</Label>
                <Select value={form.payment} onValueChange={(value: "paid" | "not_paid") => setForm((f) => ({ ...f, payment: value }))} disabled={dialogMode === "show"}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="not_paid">{t("lab.medical_analyses.not_paid")}</SelectItem>
                    <SelectItem value="paid">{t("lab.medical_analyses.paid")}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("lab.medical_analyses.doctor_name")}</Label>
                <Input value={form.doctor_name} onChange={(e) => setForm((f) => ({ ...f, doctor_name: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              {/* <div className="space-y-2">
                <Label>{t("lab.medical_analyses.reservation_id_optional")}</Label>
                <Input value={form.reservation_id} onChange={(e) => setForm((f) => ({ ...f, reservation_id: e.target.value }))} disabled={dialogMode === "show"} />
              </div> */}
            </div>

            <div className="space-y-2">
              <Label>{t("lab.medical_analyses.report")}</Label>
              <Textarea rows={3} value={form.report} onChange={(e) => setForm((f) => ({ ...f, report: e.target.value }))} disabled={dialogMode === "show"} />
            </div>

            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <Label className="text-base">{t("lab.medical_analyses.services_values_and_media")}</Label>
                {dialogMode !== "show" && (
                  <Button type="button" variant="outline" size="sm" onClick={addServiceRow}>{t("lab.medical_analyses.add_service")}</Button>
                )}
              </div>
              <div className="border rounded-lg">
                <div className="grid grid-cols-12 gap-2 p-3 border-b bg-muted/40 text-xs font-medium text-muted-foreground">
                  <div className="col-span-4">{t("lab.medical_analyses.service")}</div>
                  <div className="col-span-2">{t("lab.medical_analyses.price")}</div>
                  <div className="col-span-2">{t("lab.medical_analyses.unit")}</div>
                  <div className="col-span-2">{t("lab.medical_analyses.normal_range")}</div>
                  <div className="col-span-1">{t("lab.medical_analyses.value")}</div>
                  <div className="col-span-1">{t("lab.medical_analyses.media")}</div>
                </div>
                <div className="divide-y">
                  {form.services.length === 0 && <div className="p-3 text-sm text-muted-foreground">{t("lab.medical_analyses.no_services_selected")}</div>}
                  {form.services.map((row, idx) => {
                    const service = row.lab_service_id ? serviceMap.get(row.lab_service_id) : undefined;
                    return (
                      <div key={`${idx}-${row.option_id ?? row.lab_service_id}`} className="grid grid-cols-12 gap-2 p-3 items-center">
                        <div className="col-span-4">
                          <Select value={row.lab_service_id} onValueChange={(value) => updateServiceRow(idx, { lab_service_id: value })} disabled={dialogMode === "show"}>
                            <SelectTrigger><SelectValue placeholder={t("lab.medical_analyses.select_service")} /></SelectTrigger>
                            <SelectContent>
                              {services.map((s) => (
                                <SelectItem key={String(s.id)} value={String(s.id)}>
                                  {s.name ?? `${t("lab.medical_analyses.service")} ${s.id}`}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                        </div>
                        <div className="col-span-2 text-sm text-muted-foreground">{service?.price ?? "—"} EGP</div>
                        <div className="col-span-2 text-sm text-muted-foreground">{service?.unit ?? "—"}</div>
                        <div className="col-span-2 text-sm text-muted-foreground">{service?.normal_range ?? "—"}</div>
                        <div className="col-span-1">
                          <Input value={row.value} onChange={(e) => updateServiceRow(idx, { value: e.target.value })} disabled={dialogMode === "show"} />
                        </div>
                        <div className="col-span-1">
                          {dialogMode === "show" ? (
                            <span className="text-xs text-muted-foreground">{(row.existing_images?.length ?? 0) > 0 ? `${row.existing_images?.length} file(s)` : "No files"}</span>
                          ) : (
                            <Input type="file" multiple onChange={(e) => updateServiceRow(idx, { images: Array.from(e.target.files ?? []) })} />
                          )}
                        </div>
                        {dialogMode !== "show" && (
                          <div className="col-span-12 text-right">
                            <Button type="button" variant="ghost" size="sm" onClick={() => removeServiceRow(idx)}>{t("lab.medical_analyses.remove")}</Button>
                          </div>
                        )}
                        {dialogMode === "show" && (row.existing_images?.length ?? 0) > 0 && (
                          <div className="col-span-12 flex flex-wrap gap-2">
                            {row.existing_images?.map((url, imageIndex) => (
                              <a key={`${idx}-${imageIndex}`} href={url} target="_blank" rel="noreferrer" className="text-xs underline text-primary">
                                {t("lab.medical_analyses.view_file")} {imageIndex + 1}
                              </a>
                            ))}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("lab.medical_analyses.close")}</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? t("lab.medical_analyses.saving") : t("lab.medical_analyses.save")}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>{t("lab.medical_analyses.delete_analysis")}</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">{t("lab.medical_analyses.delete_analysis_description")}</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId("")}>{t("lab.medical_analyses.cancel")}</Button>
            <Button variant="destructive" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
              {deleteMutation.isPending ? t("lab.medical_analyses.deleting") : t("lab.medical_analyses.delete")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function toAnalysisFormData(form: {
  patient_id: string;
  reservation_id: string;
  date: string;
  doctor_name: string;
  payment: "paid" | "not_paid";
  report: string;
  services: AnalysisServiceRow[];
}): FormData {
  const fd = new FormData();
  fd.append("patient_id", form.patient_id);
  if (form.reservation_id.trim()) {
    fd.append("reservation_id", form.reservation_id.trim());
  }
  fd.append("date", form.date);
  fd.append("doctor_name", form.doctor_name.trim());
  fd.append("payment", form.payment);
  fd.append("report", form.report);

  form.services
    .filter((row) => row.lab_service_id)
    .forEach((row, index) => {
      if (row.option_id) {
        fd.append(`services[${index}][option_id]`, row.option_id);
      }
      fd.append(`services[${index}][lab_service_id]`, row.lab_service_id);
      fd.append(`services[${index}][value]`, row.value ?? "");
      row.images.forEach((file) => {
        fd.append(`services[${index}][images][]`, file);
      });
    });

  return fd;
}
