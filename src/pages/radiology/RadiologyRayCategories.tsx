import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, Plus, Edit, Eye, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { radiologyApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type RayCategoryRow = {
  id: string | number;
  name?: string;
  description?: string | null;
  created_at?: string;
};

export default function RadiologyRayCategories() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [deleteId, setDeleteId] = useState<string>("");
  const [form, setForm] = useState({ name: "", description: "" });
  const perPage = 10;
  const queryClient = useQueryClient();
  const { toast } = useToast();

  const { data, isLoading, error } = useQuery({
    queryKey: ["radiology", "ray-categories", page, perPage, search],
    queryFn: () =>
      radiologyApi.rayCategories({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const root = (data as { data?: unknown })?.data ?? data;
  const categories = ((root as { data?: RayCategoryRow[] })?.data ?? []) as RayCategoryRow[];
  const pagination = (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;

  const createMutation = useMutation({
    mutationFn: () =>
      radiologyApi.createRayCategory({
        name: form.name.trim(),
        description: form.description.trim() || null,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "ray-categories"] });
      toast({ title: "Ray category created" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to create ray category",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () =>
      radiologyApi.updateRayCategory(activeId, {
        name: form.name.trim(),
        description: form.description.trim() || null,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "ray-categories"] });
      toast({ title: "Ray category updated" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update ray category",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => radiologyApi.deleteRayCategory(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "ray-categories"] });
      toast({ title: "Ray category deleted" });
      setDeleteId("");
    },
    onError: (e) =>
      toast({
        title: "Failed to delete ray category",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ name: "", description: "" });
  };

  const openShow = async (row: RayCategoryRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    const details = await radiologyApi.rayCategory(row.id);
    const rootData = (details as { data?: unknown })?.data ?? details;
    const item = rootData as RayCategoryRow;
    setForm({
      name: item.name ?? "",
      description: item.description ?? "",
    });
  };

  const openEdit = async (row: RayCategoryRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    const details = await radiologyApi.rayCategory(row.id);
    const rootData = (details as { data?: unknown })?.data ?? details;
    const item = rootData as RayCategoryRow;
    setForm({
      name: item.name ?? "",
      description: item.description ?? "",
    });
  };

  const onSave = () => {
    if (!form.name.trim()) {
      toast({ title: "Category name is required", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") createMutation.mutate();
    if (dialogMode === "edit") updateMutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Ray Categories</h2>
          <p className="text-muted-foreground text-sm mt-1">Manage radiology ray categories</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add Category
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search categories..."
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
                <th className="text-start font-medium p-4 text-muted-foreground">Name</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Description</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Created</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={5}>Loading categories...</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={5}>{error instanceof Error ? error.message : "Failed to load categories"}</td>
                </tr>
              )}
              {!isLoading && !error && categories.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={5}>No categories found.</td>
                </tr>
              )}
              {!isLoading && !error && categories.map((c) => (
                <tr key={String(c.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(c.id)}</td>
                  <td className="p-4 font-medium">{c.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{c.description?.trim() || "—"}</td>
                  <td className="p-4 text-muted-foreground">{c.created_at ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => void openShow(c)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => void openEdit(c)}>
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
            <DialogTitle>{dialogMode === "add" ? "Add Ray Category" : dialogMode === "edit" ? "Edit Ray Category" : "Ray Category Details"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>Category Name</Label>
              <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} />
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                rows={4}
                value={form.description}
                onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>Close</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? "Saving..." : "Save"}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader><DialogTitle>Delete Category?</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">This action cannot be undone.</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId("")}>Cancel</Button>
            <Button variant="destructive" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>
              {deleteMutation.isPending ? "Deleting..." : "Delete"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
