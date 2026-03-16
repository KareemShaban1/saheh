import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

export default function ClinicInventory() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [activeTab, setActiveTab] = useState<"categories" | "movements">("categories");
  const [categoryPage, setCategoryPage] = useState(1);
  const [movementPage, setMovementPage] = useState(1);
  const [categoriesState, setCategoriesState] = useState<Array<{ id: string | number; name?: string; quantity?: number; price?: number }>>([]);
  const [movementsState, setMovementsState] = useState<Array<{ id: string | number; item?: string; type?: string; quantity?: number; date?: string }>>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [dialogTarget, setDialogTarget] = useState<"categories" | "movements">("categories");
  const [activeId, setActiveId] = useState<string>("");
  const [categoryForm, setCategoryForm] = useState({ name: "", quantity: "0", price: "0" });
  const [movementForm, setMovementForm] = useState({ item: "", type: "in", quantity: "0", date: "" });
  const { toast } = useToast();
  const perPage = 10;
  const categoriesQuery = useQuery({
    queryKey: ["clinic", "inventory-categories"],
    queryFn: () => clinicApi.inventoryCategories(),
  });
  const movementsQuery = useQuery({
    queryKey: ["clinic", "inventory-movements"],
    queryFn: () => clinicApi.inventoryMovements(),
  });

  const categories = useMemo<Array<{ id: string | number; name?: string; quantity?: number; price?: number }>>(() => {
    const root = (categoriesQuery.data as { data?: unknown })?.data ?? categoriesQuery.data;
    return (root as { data?: Array<{ id: string | number; name?: string; quantity?: number; price?: number }> })?.data ?? [];
  }, [categoriesQuery.data]);
  const movements = useMemo<Array<{ id: string | number; item?: string; type?: string; quantity?: number; date?: string }>>(() => {
    const root = (movementsQuery.data as { data?: unknown })?.data ?? movementsQuery.data;
    return (root as { data?: Array<{ id: string | number; item?: string; type?: string; quantity?: number; date?: string }> })?.data ?? [];
  }, [movementsQuery.data]);

  const filteredCategories = categories.filter((c) => (c.name ?? "").toLowerCase().includes(search.toLowerCase()));
  const filteredMovements = movements.filter((m) => (m.item ?? "").toLowerCase().includes(search.toLowerCase()));
  useEffect(() => setCategoriesState(categories), [categories]);
  useEffect(() => setMovementsState(movements), [movements]);

  const categoriesFiltered = categoriesState.filter((c) => (c.name ?? "").toLowerCase().includes(search.toLowerCase()));
  const movementsFiltered = movementsState.filter((m) => (m.item ?? "").toLowerCase().includes(search.toLowerCase()));
  const categoriesTotalPages = Math.max(1, Math.ceil(categoriesFiltered.length / perPage));
  const movementsTotalPages = Math.max(1, Math.ceil(movementsFiltered.length / perPage));
  const safeCategoryPage = Math.min(categoryPage, categoriesTotalPages);
  const safeMovementPage = Math.min(movementPage, movementsTotalPages);
  const pagedCategories = categoriesFiltered.slice((safeCategoryPage - 1) * perPage, safeCategoryPage * perPage);
  const pagedMovements = movementsFiltered.slice((safeMovementPage - 1) * perPage, safeMovementPage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setDialogTarget(activeTab);
    setActiveId("");
    setCategoryForm({ name: "", quantity: "0", price: "0" });
    setMovementForm({ item: "", type: "in", quantity: "0", date: "" });
  };

  const openShowCategory = (c: { id: string | number; name?: string; quantity?: number; price?: number }) => {
    setDialogMode("show");
    setDialogTarget("categories");
    setActiveId(String(c.id));
    setCategoryForm({ name: c.name ?? "", quantity: String(c.quantity ?? 0), price: String(c.price ?? 0) });
  };
  const openEditCategory = (c: { id: string | number; name?: string; quantity?: number; price?: number }) => {
    setDialogMode("edit");
    setDialogTarget("categories");
    setActiveId(String(c.id));
    setCategoryForm({ name: c.name ?? "", quantity: String(c.quantity ?? 0), price: String(c.price ?? 0) });
  };
  const openShowMovement = (m: { id: string | number; item?: string; type?: string; quantity?: number; date?: string }) => {
    setDialogMode("show");
    setDialogTarget("movements");
    setActiveId(String(m.id));
    setMovementForm({ item: m.item ?? "", type: m.type ?? "in", quantity: String(m.quantity ?? 0), date: m.date ?? "" });
  };
  const openEditMovement = (m: { id: string | number; item?: string; type?: string; quantity?: number; date?: string }) => {
    setDialogMode("edit");
    setDialogTarget("movements");
    setActiveId(String(m.id));
    setMovementForm({ item: m.item ?? "", type: m.type ?? "in", quantity: String(m.quantity ?? 0), date: m.date ?? "" });
  };

  const onSave = () => {
    if (dialogTarget === "categories") {
      if (!categoryForm.name.trim()) {
        toast({ title: t("clinic.inventory.category_name_is_required"), variant: "destructive" });
        return;
      }
      if (dialogMode === "add") {
        setCategoriesState((prev) => [{ id: `local-${Date.now()}`, name: categoryForm.name, quantity: Number(categoryForm.quantity || 0), price: Number(categoryForm.price || 0) }, ...prev]);
        toast({ title: t("clinic.inventory.category_added") });
      } else if (dialogMode === "edit") {
        setCategoriesState((prev) => prev.map((c) => (String(c.id) === activeId ? { ...c, name: categoryForm.name, quantity: Number(categoryForm.quantity || 0), price: Number(categoryForm.price || 0) } : c)));
        toast({ title: t("clinic.inventory.category_updated") });
      }
    } else {
      if (!movementForm.item.trim()) {
        toast({ title: t("clinic.inventory.movement_item_is_required"), variant: "destructive" });
        return;
      }
      if (dialogMode === "add") {
        setMovementsState((prev) => [{ id: `local-${Date.now()}`, item: movementForm.item, type: movementForm.type, quantity: Number(movementForm.quantity || 0), date: movementForm.date || new Date().toISOString().slice(0, 10) }, ...prev]);
        toast({ title: t("clinic.inventory.movement_added") });
      } else if (dialogMode === "edit") {
        setMovementsState((prev) => prev.map((m) => (String(m.id) === activeId ? { ...m, item: movementForm.item, type: movementForm.type, quantity: Number(movementForm.quantity || 0), date: movementForm.date } : m)));
        toast({ title: t("clinic.inventory.movement_updated") });
      }
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{t("clinic.inventory.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2"><Plus className="h-4 w-4" />{t("clinic.inventory.add")} {activeTab === "categories" ? t("clinic.inventory.category") : t("clinic.inventory.movement")}</Button>
      </div>
      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input value={search} onChange={(e) => { setSearch(e.target.value); setCategoryPage(1); setMovementPage(1); }} className="pl-10" placeholder={t("clinic.inventory.search")} />
      </div>

      <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as "categories" | "movements")}>
        <TabsList className="mb-4">
          <TabsTrigger value="categories">{t("clinic.inventory.categories")}</TabsTrigger>
          <TabsTrigger value="movements">{t("clinic.inventory.movements")}</TabsTrigger>
        </TabsList>
        <TabsContent value="categories">
          <div className="bg-card rounded-xl border shadow-card overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead><tr className="border-b bg-muted/50"><th className="text-start font-medium p-4 text-muted-foreground">#</th><th className="text-start font-medium p-4 text-muted-foreground">Name</th><th className="text-start font-medium p-4 text-muted-foreground">Quantity</th><th className="text-start font-medium p-4 text-muted-foreground">Price</th><th className="text-start font-medium p-4 text-muted-foreground">Actions</th></tr></thead>
                <tbody className="divide-y">
                  {categoriesQuery.isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={5}>{t("clinic.inventory.loading_categories")}</td></tr>}
                  {categoriesQuery.error && <tr><td className="p-4 text-destructive" colSpan={5}>{categoriesQuery.error instanceof Error ? categoriesQuery.error.message : t("clinic.inventory.failed_to_load_categories")}</td></tr>}
                  {!categoriesQuery.isLoading && !categoriesQuery.error && pagedCategories.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={5}>{t("clinic.inventory.no_categories_found")}</td></tr>}
                  {pagedCategories.map((c) => (
                    <tr key={String(c.id)} className="hover:bg-muted/30 transition-colors">
                      <td className="p-4 text-muted-foreground">{String(c.id)}</td>
                      <td className="p-4 font-medium">{c.name ?? "—"}</td>
                      <td className="p-4 text-muted-foreground">{c.quantity ?? 0}</td>
                      <td className="p-4 text-muted-foreground">{c.price ?? 0}</td>
                      <td className="p-4"><div className="flex gap-2"><Button variant="outline" size="sm" className="gap-2" onClick={() => openShowCategory(c)}><Eye className="h-4 w-4" />{t("clinic.inventory.show")}</Button><Button variant="outline" size="sm" className="gap-2" onClick={() => openEditCategory(c)}><Edit className="h-4 w-4" />{t("clinic.inventory.edit")}</Button></div></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {!categoriesQuery.isLoading && !categoriesQuery.error && categoriesTotalPages > 1 && (
              <div className="flex items-center justify-between p-4 border-t">
                <p className="text-sm text-muted-foreground">{t("clinic.inventory.page")} {safeCategoryPage} {t("clinic.inventory.of")} {categoriesTotalPages}</p>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => setCategoryPage((p) => Math.max(1, p - 1))} disabled={safeCategoryPage <= 1}>{t("clinic.inventory.previous")}</Button>
                  <Button variant="outline" size="sm" onClick={() => setCategoryPage((p) => Math.min(categoriesTotalPages, p + 1))} disabled={safeCategoryPage >= categoriesTotalPages}>{t("clinic.inventory.next")}</Button>
                </div>
              </div>
            )}
          </div>
        </TabsContent>
        <TabsContent value="movements">
          <div className="bg-card rounded-xl border shadow-card overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead><tr className="border-b bg-muted/50"><th className="text-start font-medium p-4 text-muted-foreground">#</th><th className="text-start font-medium p-4 text-muted-foreground">Item</th><th className="text-start font-medium p-4 text-muted-foreground">Type</th><th className="text-start font-medium p-4 text-muted-foreground">Quantity</th><th className="text-start font-medium p-4 text-muted-foreground">Date</th><th className="text-start font-medium p-4 text-muted-foreground">Actions</th></tr></thead>
                <tbody className="divide-y">
                  {movementsQuery.isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={6}>{t("clinic.inventory.loading_movements")}</td></tr>}
                  {movementsQuery.error && <tr><td className="p-4 text-destructive" colSpan={6}>{movementsQuery.error instanceof Error ? movementsQuery.error.message : t("clinic.inventory.failed_to_load_movements")}</td></tr>}
                  {!movementsQuery.isLoading && !movementsQuery.error && pagedMovements.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={6}>{t("clinic.inventory.no_movements_found")}</td></tr>}
                  {pagedMovements.map((m) => (
                    <tr key={String(m.id)} className="hover:bg-muted/30 transition-colors">
                      <td className="p-4 text-muted-foreground">{String(m.id)}</td>
                      <td className="p-4 font-medium">{m.item ?? "—"}</td>
                      <td className="p-4 text-muted-foreground">{m.type ?? "—"}</td>
                      <td className="p-4 text-muted-foreground">{m.quantity ?? 0}</td>
                      <td className="p-4 text-muted-foreground">{m.date ?? "—"}</td>
                      <td className="p-4"><div className="flex gap-2"><Button variant="outline" size="sm" className="gap-2" onClick={() => openShowMovement(m)}><Eye className="h-4 w-4" />Show</Button><Button variant="outline" size="sm" className="gap-2" onClick={() => openEditMovement(m)}><Edit className="h-4 w-4" />Edit</Button></div></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {!movementsQuery.isLoading && !movementsQuery.error && movementsTotalPages > 1 && (
              <div className="flex items-center justify-between p-4 border-t">
                <p className="text-sm text-muted-foreground">{t("clinic.inventory.page")} {safeMovementPage} {t("clinic.inventory.of")} {movementsTotalPages}</p>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => setMovementPage((p) => Math.max(1, p - 1))} disabled={safeMovementPage <= 1}>{t("clinic.inventory.previous")}</Button>
                  <Button variant="outline" size="sm" onClick={() => setMovementPage((p) => Math.min(movementsTotalPages, p + 1))} disabled={safeMovementPage >= movementsTotalPages}>{t("clinic.inventory.next")}</Button>
                </div>
              </div>
            )}
          </div>
        </TabsContent>
      </Tabs>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>{dialogMode === "add" ? t("clinic.inventory.add") + " " + (dialogTarget === "categories" ? t("clinic.inventory.category") : t("clinic.inventory.movement")) : dialogMode === "edit" ? t("clinic.inventory.edit") + " " + (dialogTarget === "categories" ? t("clinic.inventory.category") : t("clinic.inventory.movement")) : t("clinic.inventory.details") + " " + (dialogTarget === "categories" ? t("clinic.inventory.category") : t("clinic.inventory.movement"))}</DialogTitle></DialogHeader>
          {dialogTarget === "categories" ? (
            <div className="grid gap-4 py-2">
              <div className="space-y-2"><Label>{t("clinic.inventory.name")}</Label><Input value={categoryForm.name} onChange={(e) => setCategoryForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="grid sm:grid-cols-2 gap-4">
                <div className="space-y-2"><Label>{t("clinic.inventory.quantity")}</Label><Input type="number" value={categoryForm.quantity} onChange={(e) => setCategoryForm((f) => ({ ...f, quantity: e.target.value }))} disabled={dialogMode === "show"} /></div>
                <div className="space-y-2"><Label>{t("clinic.inventory.price")}</Label><Input type="number" value={categoryForm.price} onChange={(e) => setCategoryForm((f) => ({ ...f, price: e.target.value }))} disabled={dialogMode === "show"} /></div>
              </div>
            </div>
          ) : (
            <div className="grid gap-4 py-2">
              <div className="space-y-2"><Label>{t("clinic.inventory.item")}</Label><Input value={movementForm.item} onChange={(e) => setMovementForm((f) => ({ ...f, item: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="grid sm:grid-cols-3 gap-4">
                <div className="space-y-2"><Label>{t("clinic.inventory.type")}</Label><Input value={movementForm.type} onChange={(e) => setMovementForm((f) => ({ ...f, type: e.target.value }))} disabled={dialogMode === "show"} /></div>
                <div className="space-y-2"><Label>{t("clinic.inventory.quantity")}</Label><Input type="number" value={movementForm.quantity} onChange={(e) => setMovementForm((f) => ({ ...f, quantity: e.target.value }))} disabled={dialogMode === "show"} /></div>
                <div className="space-y-2"><Label>{t("clinic.inventory.date")}</Label><Input type="date" value={movementForm.date} onChange={(e) => setMovementForm((f) => ({ ...f, date: e.target.value }))} disabled={dialogMode === "show"} /></div>
              </div>
            </div>
          )}
          <DialogFooter><Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.inventory.close")}</Button>{dialogMode !== "show" && <Button onClick={onSave}>{t("clinic.inventory.save")}</Button>}</DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
