import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, Plus, Eye, Edit, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/hooks/use-toast";
import { labApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

type ServiceCategory = {
  id: string | number;
  category_name?: string;
};

type ServiceRow = {
  id: string | number;
  name?: string;
  category_name?: string;
  price?: string;
  unit?: string;
  normal_range?: string;
  notes?: string;
};

export default function LabServices() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [categoryId, setCategoryId] = useState("all");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState("");
  const [deleteId, setDeleteId] = useState("");
  const [form, setForm] = useState({
    lab_service_category_id: "",
    name: "",
    price: "0",
    unit: "",
    normal_range: "",
    notes: "",
  });
  const perPage = 10;
  const queryClient = useQueryClient();
  const { toast } = useToast();

  const categoriesQuery = useQuery({
    queryKey: ["lab", "service-categories-options"],
    queryFn: () => labApi.serviceCategories({ per_page: "200" }),
  });

  const { data, isLoading, error } = useQuery({
    queryKey: ["lab", "services", page, perPage, search, categoryId],
    queryFn: () =>
      labApi.services({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
        ...(categoryId !== "all" ? { category_id: categoryId } : {}),
      }),
  });

  const servicesRoot = (data as { data?: unknown })?.data ?? data;
  const services = ((servicesRoot as { data?: ServiceRow[] })?.data ?? []) as ServiceRow[];
  const pagination = (servicesRoot as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;

  const categoriesRoot = (categoriesQuery.data as { data?: unknown })?.data ?? categoriesQuery.data;
  const categories = ((categoriesRoot as { data?: ServiceCategory[] })?.data ?? []) as ServiceCategory[];

  const createMutation = useMutation({
    mutationFn: () =>
      labApi.createService({
        lab_service_category_id: Number(form.lab_service_category_id),
        name: form.name.trim(),
        price: Number(form.price),
        unit: form.unit.trim(),
        normal_range: form.normal_range.trim(),
        notes: form.notes.trim() || null,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "services"] });
      toast({ title: "Service created" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({ title: "Failed to create service", description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const updateMutation = useMutation({
    mutationFn: () =>
      labApi.updateService(activeId, {
        lab_service_category_id: Number(form.lab_service_category_id),
        name: form.name.trim(),
        price: Number(form.price),
        unit: form.unit.trim(),
        normal_range: form.normal_range.trim(),
        notes: form.notes.trim() || null,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "services"] });
      toast({ title: "Service updated" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({ title: "Failed to update service", description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => labApi.deleteService(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "services"] });
      toast({ title: "Service deleted" });
      setDeleteId("");
    },
    onError: (e) =>
      toast({ title: "Failed to delete service", description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({
      lab_service_category_id: "",
      name: "",
      price: "0",
      unit: "",
      normal_range: "",
      notes: "",
    });
  };

  const hydrateFromDetails = (item: ServiceRow & { lab_service_category_id?: string | number | null }) => {
    setForm({
      lab_service_category_id: item.lab_service_category_id ? String(item.lab_service_category_id) : "",
      name: item.name ?? "",
      price: item.price ?? "0",
      unit: item.unit ?? "",
      normal_range: item.normal_range ?? "",
      notes: item.notes ?? "",
    });
  };

  const openShow = async (row: ServiceRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    const details = await labApi.service(row.id);
    const rootData = (details as { data?: unknown })?.data ?? details;
    hydrateFromDetails(rootData as ServiceRow & { lab_service_category_id?: string | number | null });
  };

  const openEdit = async (row: ServiceRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    const details = await labApi.service(row.id);
    const rootData = (details as { data?: unknown })?.data ?? details;
    hydrateFromDetails(rootData as ServiceRow & { lab_service_category_id?: string | number | null });
  };

  const onSave = () => {
    if (!form.lab_service_category_id || !form.name.trim() || !form.unit.trim() || !form.normal_range.trim()) {
      toast({ title: "Category, name, unit, and normal range are required", variant: "destructive" });
      return;
    }
    const priceNumber = Number(form.price);
    if (Number.isNaN(priceNumber) || priceNumber < 0) {
      toast({ title: "Price must be a valid non-negative number", variant: "destructive" });
      return;
    }

    if (dialogMode === "add") createMutation.mutate();
    if (dialogMode === "edit") updateMutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold"> {t("lab.services.title")}</h2>
        <p className="text-muted-foreground text-sm mt-1"> {t("lab.services.description")}</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("lab.services.add")}
        </Button>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder={t("lab.services.search")}
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            className="pl-10"
          />
        </div>
        <Select
          value={categoryId}
          onValueChange={(value) => {
            setCategoryId(value);
            setPage(1);
          }}
        >
          <SelectTrigger className="w-[220px]"><SelectValue placeholder={t("lab.services.category")} /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t("lab.services.all_categories")}</SelectItem>
            {categories.map((c) => (
              <SelectItem key={String(c.id)} value={String(c.id)}>
                {c.category_name ?? `${t("lab.services.category")} ${c.id}`}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.category")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.price")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.unit")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.normal_range")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.notes")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.services.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>{t("lab.services.loading_services")}</td></tr>
              )}
              {error && (
                <tr><td className="p-4 text-destructive" colSpan={8}>{error instanceof Error ? error.message : t("lab.services.failed_to_load_services")}</td></tr>
              )}
              {!isLoading && !error && services.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>No services found.</td></tr>
              )}
              {!isLoading && !error && services.map((s) => (
                <tr key={String(s.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(s.id)}</td>
                  <td className="p-4 font-medium">{s.name ?? "—"}</td>
                  <td className="p-4"><Badge variant="outline">{s.category_name ?? "—"}</Badge></td>
                  <td className="p-4 text-muted-foreground">{s.price ?? "0"} EGP</td>
                  <td className="p-4 text-muted-foreground">{s.unit ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{s.normal_range ?? "—"}</td>
                  <td className="p-4 text-muted-foreground max-w-[280px] truncate">{s.notes ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(s)}>
                        <Eye className="h-4 w-4" />
                        {t("lab.services.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(s)}>
                        <Edit className="h-4 w-4" />
                        {t("lab.services.edit")}
                      </Button>
                      <Button variant="destructive" size="sm" className="gap-2" onClick={() => setDeleteId(String(s.id))}>
                        <Trash2 className="h-4 w-4" />
                        {t("lab.services.delete")}
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
            {t("lab.services.page")} {pagination?.current_page ?? page} {t("lab.services.of")} {pagination?.last_page ?? 1}
            {typeof pagination?.total === "number" ? ` (${pagination.total} total)` : ""}
          </p>
          <div className="flex gap-1">
            <Button variant="outline" size="icon" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={(pagination?.current_page ?? page) <= 1}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button variant="outline" size="icon" onClick={() => setPage((p) => Math.min(pagination?.last_page ?? p, p + 1))} disabled={(pagination?.current_page ?? page) >= (pagination?.last_page ?? 1)}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-2xl">
          <DialogHeader><DialogTitle>{dialogMode === "add" ? t("lab.services.add") : dialogMode === "edit" ? t("lab.services.edit") : t("lab.services.details")}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("lab.services.category")}</Label>
                <Select
                  value={form.lab_service_category_id}
                  onValueChange={(value) => setForm((f) => ({ ...f, lab_service_category_id: value }))}
                  disabled={dialogMode === "show"}
                >
                  <SelectTrigger><SelectValue placeholder={t("lab.services.select_category")} /></SelectTrigger>
                  <SelectContent>
                    {categories.map((c) => (
                      <SelectItem key={String(c.id)} value={String(c.id)}>
                        {c.category_name ?? `Category ${c.id}`}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>{t("lab.services.name")}</Label>
                <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>{t("lab.services.price")}</Label>
                <Input type="number" min="0" step="0.01" value={form.price} onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("lab.services.unit")}</Label>
                <Input value={form.unit} onChange={(e) => setForm((f) => ({ ...f, unit: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("lab.services.normal_range")}</Label>
                <Input value={form.normal_range} onChange={(e) => setForm((f) => ({ ...f, normal_range: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="space-y-2">
              <Label>{t("lab.services.notes")}</Label>
              <Textarea value={form.notes} onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))} disabled={dialogMode === "show"} rows={4} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("lab.services.close")}</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? t("lab.services.saving") : t("lab.services.save")}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>{t("lab.services.delete_confirmation")}</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">{t("lab.services.delete_confirmation_description")}</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId("")}>{t("lab.services.cancel")}</Button>
            <Button variant="destructive" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
              {deleteMutation.isPending ? t("lab.services.deleting") : t("lab.services.delete")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
