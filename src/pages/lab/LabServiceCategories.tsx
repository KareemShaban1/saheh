import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, Plus, Edit, Eye, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";
import { labApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

type CategoryRow = {
  id: string | number;
  category_name?: string;
  is_active?: boolean;
  status?: string;
  services_count?: number;
  created_at?: string;
};

export default function LabServiceCategories() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [deleteId, setDeleteId] = useState<string>("");
  const [form, setForm] = useState({ category_name: "", is_active: "true" });
  const perPage = 10;
  const queryClient = useQueryClient();
  const { toast } = useToast();

  const { data, isLoading, error } = useQuery({
    queryKey: ["lab", "service-categories", page, perPage, search],
    queryFn: () =>
      labApi.serviceCategories({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const root = (data as { data?: unknown })?.data ?? data;
  const categories = ((root as { data?: CategoryRow[] })?.data ?? []) as CategoryRow[];
  const pagination = (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;

  const createMutation = useMutation({
    mutationFn: () =>
      labApi.createServiceCategory({
        category_name: form.category_name.trim(),
        is_active: form.is_active === "true",
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "service-categories"] });
      toast({ title: t("lab.service_categories.category_created") });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({ title: "Failed to create category", description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const updateMutation = useMutation({
    mutationFn: () =>
      labApi.updateServiceCategory(activeId, {
        category_name: form.category_name.trim(),
        is_active: form.is_active === "true",
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "service-categories"] });
      toast({ title: t("lab.service_categories.category_updated") });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({ title: t("lab.service_categories.failed_to_update_category"), description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => labApi.deleteServiceCategory(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "service-categories"] });
      toast({ title: t("lab.service_categories.category_deleted") });
      setDeleteId("");
    },
    onError: (e) =>
      toast({ title: t("lab.service_categories.failed_to_delete_category"), description: e instanceof Error ? e.message : "Unknown error", variant: "destructive" }),
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ category_name: "", is_active: "true" });
  };

  const openShow = async (row: CategoryRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    const details = await labApi.serviceCategory(row.id);
    const rootData = (details as { data?: unknown })?.data ?? details;
    const item = rootData as CategoryRow;
    setForm({
      category_name: item.category_name ?? "",
      is_active: item.is_active ? "true" : "false",
    });
  };

  const openEdit = async (row: CategoryRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    const details = await labApi.serviceCategory(row.id);
    const rootData = (details as { data?: unknown })?.data ?? details;
    const item = rootData as CategoryRow;
    setForm({
      category_name: item.category_name ?? "",
      is_active: item.is_active ? "true" : "false",
    });
  };

  const onSave = () => {
    if (!form.category_name.trim()) {
      toast({ title: t("lab.service_categories.category_name_required"), variant: "destructive" });
      return;
    }
    if (dialogMode === "add") createMutation.mutate();
    if (dialogMode === "edit") updateMutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold"> {t("lab.service_categories.title")}</h2>
        <p className="text-muted-foreground text-sm mt-1"> {t("lab.service_categories.description")}</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("lab.service_categories.add")}
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("lab.service_categories.search")}
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
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.service_categories.category")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.service_categories.services")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.service_categories.status")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.service_categories.created")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.service_categories.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={6}>{t("lab.service_categories.loading_categories")}</td></tr>
              )}
              {error && (
                <tr><td className="p-4 text-destructive" colSpan={6}>{error instanceof Error ? error.message : "Failed to load categories"}</td></tr>
              )}
              {!isLoading && !error && categories.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={6}>No categories found.</td></tr>
              )}
              {!isLoading && !error && categories.map((c) => (
                <tr key={String(c.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(c.id)}</td>
                  <td className="p-4 font-medium">{c.category_name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{c.services_count ?? 0}</td>
                  <td className="p-4">
                    <Badge variant={(c.status ?? "inactive") === "active" ? "default" : "secondary"}>{c.status ?? "inactive"}</Badge>
                  </td>
                  <td className="p-4 text-muted-foreground">{c.created_at ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(c)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(c)}>
                        <Edit className="h-4 w-4" />
                        Edit
                      </Button>
                      <Button variant="destructive" size="sm" className="gap-2" onClick={() => setDeleteId(String(c.id))}>
                        <Trash2 className="h-4 w-4" />
                        Delete
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
            Page {pagination?.current_page ?? page} of {pagination?.last_page ?? 1}
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
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{dialogMode === "add" ? t("lab.service_categories.add") : dialogMode === "edit" ? t("lab.service_categories.edit") : t("lab.service_categories.details")}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>{t("lab.service_categories.category_name")}</Label>
              <Input value={form.category_name} onChange={(e) => setForm((f) => ({ ...f, category_name: e.target.value }))} disabled={dialogMode === "show"} />
            </div>
            <div className="space-y-2">
              <Label>{t("lab.service_categories.status")}</Label>
              <Select value={form.is_active} onValueChange={(value) => setForm((f) => ({ ...f, is_active: value }))} disabled={dialogMode === "show"}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="true">{t("lab.service_categories.active")}</SelectItem>
                  <SelectItem value="false">{t("lab.service_categories.inactive")}</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("lab.service_categories.close")}</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? t("lab.service_categories.saving") : t("lab.service_categories.save")}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>Delete Category?</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">{t("lab.service_categories.delete_confirmation")}</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId("")}>Cancel</Button>
            <Button variant="destructive" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
              {deleteMutation.isPending ? t("lab.service_categories.deleting") : t("lab.service_categories.delete")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
