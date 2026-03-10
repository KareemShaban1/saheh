import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { adminApi } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/hooks/use-toast";

type SpecialtyRow = {
  id: string | number;
  name_en: string;
  name_ar: string;
  description: string;
  status: number;
};

export default function AdminSpecialties() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [createOpen, setCreateOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [detailsLoading, setDetailsLoading] = useState(false);
  const [detailsData, setDetailsData] = useState<Record<string, unknown> | null>(null);
  const [activeRow, setActiveRow] = useState<SpecialtyRow | null>(null);
  const [form, setForm] = useState({
    name_en: "",
    name_ar: "",
    description: "",
    status: "active" as "active" | "inactive",
  });
  const { data, isLoading, error } = useQuery({
    queryKey: ["admin", "specialties"],
    queryFn: () => adminApi.specialties(),
  });

  const rows = useMemo<SpecialtyRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (Array.isArray(root) ? root : []).map((specialty) => {
      const row = specialty as Record<string, unknown>;
      return {
        id: String(row.id ?? "—"),
        name_en: String(row.name_en ?? "—"),
        name_ar: String(row.name_ar ?? "—"),
        description: String(row.description ?? "—"),
        status: Number(row.status ?? 1),
      };
    });
  }, [data]);

  const createMutation = useMutation({
    mutationFn: (payload: { name_en: string; name_ar: string; description: string; status: "active" | "inactive" }) => adminApi.createSpecialty(payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "specialties"] });
      toast({ title: "Specialty created" });
      setCreateOpen(false);
      setForm({ name_en: "", name_ar: "", description: "", status: "active" });
    },
    onError: (e) =>
      toast({
        title: "Failed to create specialty",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });
  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string | number; payload: { name_en: string; name_ar: string; description: string } }) =>
      adminApi.updateSpecialty(id, payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "specialties"] });
      toast({ title: "Specialty updated" });
      setEditOpen(false);
      setActiveRow(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update specialty",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });
  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: "active" | "inactive" }) => adminApi.updateSpecialtyStatus(id, status),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "specialties"] });
      toast({ title: "Specialty status updated" });
    },
  });
  const deleteMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteSpecialty(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "specialties"] });
      toast({ title: "Specialty deleted" });
      setDeleteOpen(false);
      setActiveRow(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to delete specialty",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const onAdd = () => {
    setForm({ name_en: "", name_ar: "", description: "", status: "active" });
    setCreateOpen(true);
  };

  const onShow = async (id: string | number) => {
    try {
      setDetailsLoading(true);
      setDetailsOpen(true);
      const res = await adminApi.specialty(id);
      const root = (res as { data?: unknown })?.data ?? {};
      const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
      setDetailsData(row);
    } catch (e) {
      toast({
        title: "Failed to load specialty details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDetailsOpen(false);
    } finally {
      setDetailsLoading(false);
    }
  };

  const onEdit = (row: SpecialtyRow) => {
    setActiveRow(row);
    setForm({
      name_en: row.name_en === "—" ? "" : row.name_en,
      name_ar: row.name_ar === "—" ? "" : row.name_ar,
      description: row.description === "—" ? "" : row.description,
      status: row.status === 1 ? "active" : "inactive",
    });
    setEditOpen(true);
  };

  const onOpenDelete = (row: SpecialtyRow) => {
    setActiveRow(row);
    setDeleteOpen(true);
  };

  const submitCreate = () => {
    if (!form.name_en.trim()) {
      toast({ title: "Name (EN) is required", variant: "destructive" });
      return;
    }
    createMutation.mutate({
      name_en: form.name_en.trim(),
      name_ar: form.name_ar.trim(),
      description: form.description.trim(),
      status: form.status,
    });
  };

  const submitEdit = () => {
    if (!activeRow) return;
    if (!form.name_en.trim()) {
      toast({ title: "Name (EN) is required", variant: "destructive" });
      return;
    }
    updateMutation.mutate({
      id: activeRow.id,
      payload: {
        name_en: form.name_en.trim(),
        name_ar: form.name_ar.trim(),
        description: form.description.trim(),
      },
    });
  };

  const submitDelete = () => {
    if (!activeRow) return;
    deleteMutation.mutate(activeRow.id);
  };

  if (isLoading) return <div className="text-sm text-muted-foreground">Loading specialties...</div>;
  if (error) return <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load specialties"}</div>;

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <h2 className="text-2xl font-bold">Specialties</h2>
        <Button onClick={onAdd} className="gradient-primary text-primary-foreground border-0">Add Specialty</Button>
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Name (EN)</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Name (AR)</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Description</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {rows.map((row) => (
                <tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(row.id)}</td>
                  <td className="p-4">{row.name_en}</td>
                  <td className="p-4">{row.name_ar}</td>
                  <td className="p-4">{row.description || "—"}</td>
                  <td className="p-4">
                    <Badge variant="secondary" className={row.status === 1 ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}>
                      {row.status === 1 ? "active" : "inactive"}
                    </Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      <Button variant="outline" size="sm" onClick={() => onShow(row.id)}>Show</Button>
                      <Button variant="outline" size="sm" onClick={() => onEdit(row)}>Edit</Button>
                      <Button variant="outline" size="sm" onClick={() => statusMutation.mutate({ id: row.id, status: row.status === 1 ? "inactive" : "active" })}>
                        {row.status === 1 ? "Deactivate" : "Activate"}
                      </Button>
                      <Button variant="destructive" size="sm" onClick={() => onOpenDelete(row)}>
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Add Specialty</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label htmlFor="specialty-create-name-en">Name (EN) *</Label>
              <Input
                id="specialty-create-name-en"
                value={form.name_en}
                onChange={(e) => setForm((prev) => ({ ...prev, name_en: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="specialty-create-name-ar">Name (AR)</Label>
              <Input
                id="specialty-create-name-ar"
                value={form.name_ar}
                onChange={(e) => setForm((prev) => ({ ...prev, name_ar: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="specialty-create-description">Description</Label>
              <Textarea
                id="specialty-create-description"
                rows={3}
                value={form.description}
                onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="specialty-create-status">Status</Label>
              <select
                id="specialty-create-status"
                title="Specialty status"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.status}
                onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value as "active" | "inactive" }))}
              >
                <option value="active">active</option>
                <option value="inactive">inactive</option>
              </select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateOpen(false)}>Cancel</Button>
            <Button onClick={submitCreate} disabled={createMutation.isPending}>
              {createMutation.isPending ? "Saving..." : "Save"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Edit Specialty</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label htmlFor="specialty-edit-name-en">Name (EN) *</Label>
              <Input
                id="specialty-edit-name-en"
                value={form.name_en}
                onChange={(e) => setForm((prev) => ({ ...prev, name_en: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="specialty-edit-name-ar">Name (AR)</Label>
              <Input
                id="specialty-edit-name-ar"
                value={form.name_ar}
                onChange={(e) => setForm((prev) => ({ ...prev, name_ar: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="specialty-edit-description">Description</Label>
              <Textarea
                id="specialty-edit-description"
                rows={3}
                value={form.description}
                onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
              />
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setEditOpen(false);
                setActiveRow(null);
              }}
            >
              Cancel
            </Button>
            <Button onClick={submitEdit} disabled={updateMutation.isPending}>
              {updateMutation.isPending ? "Saving..." : "Save"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <Dialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Delete Specialty</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">
            Delete specialty <span className="font-medium text-foreground">"{activeRow?.name_en ?? "—"}"</span>? This action cannot be undone.
          </p>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setDeleteOpen(false);
                setActiveRow(null);
              }}
            >
              Cancel
            </Button>
            <Button variant="destructive" onClick={submitDelete} disabled={deleteMutation.isPending}>
              {deleteMutation.isPending ? "Deleting..." : "Delete"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <Dialog open={detailsOpen} onOpenChange={setDetailsOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Specialty Details</DialogTitle>
          </DialogHeader>
          {detailsLoading ? (
            <div className="py-3 text-sm text-muted-foreground">Loading...</div>
          ) : (
            <div className="space-y-2 text-sm">
              <p><span className="font-medium">Name (EN):</span> {String(detailsData?.name_en ?? "—")}</p>
              <p><span className="font-medium">Name (AR):</span> {String(detailsData?.name_ar ?? "—")}</p>
              <p><span className="font-medium">Description:</span> {String(detailsData?.description ?? "—")}</p>
              <p><span className="font-medium">Status:</span> {Number(detailsData?.status ?? 1) === 1 ? "active" : "inactive"}</p>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setDetailsOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
