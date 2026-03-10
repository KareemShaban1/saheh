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

type AnnouncementRow = {
  id: string | number;
  title: string;
  body: string;
  is_active: boolean;
};

export default function AdminAnnouncements() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [createOpen, setCreateOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [detailsLoading, setDetailsLoading] = useState(false);
  const [detailsData, setDetailsData] = useState<Record<string, unknown> | null>(null);
  const [activeRow, setActiveRow] = useState<AnnouncementRow | null>(null);
  const [form, setForm] = useState({
    title: "",
    body: "",
    type: "text" as "text" | "banner",
    is_active: true,
  });
  const { data, isLoading, error } = useQuery({
    queryKey: ["admin", "announcements"],
    queryFn: () => adminApi.announcements({ per_page: "100" }),
  });

  const rows = useMemo<AnnouncementRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return ((root as { data?: Array<Record<string, unknown>> })?.data ?? []).map((announcement) => ({
      id: String(announcement.id ?? "—"),
      title: String(announcement.title ?? "—"),
      body: String(announcement.body ?? "—"),
      is_active: Boolean(announcement.is_active ?? true),
    }));
  }, [data]);

  const createMutation = useMutation({
    mutationFn: (payload: { title: string; body: string; type?: "text" | "banner"; is_active?: boolean }) => adminApi.createAnnouncement(payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "announcements"] });
      toast({ title: "Announcement created" });
      setCreateOpen(false);
      setForm({ title: "", body: "", type: "text", is_active: true });
    },
    onError: (e) =>
      toast({
        title: "Failed to create announcement",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });
  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string | number; payload: { title: string; body: string; type?: "text" | "banner" } }) =>
      adminApi.updateAnnouncement(id, payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "announcements"] });
      toast({ title: "Announcement updated" });
      setEditOpen(false);
      setActiveRow(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update announcement",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });
  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: "active" | "inactive" }) => adminApi.updateAnnouncementStatus(id, status),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "announcements"] });
      toast({ title: "Announcement status updated" });
    },
  });
  const deleteMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteAnnouncement(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "announcements"] });
      toast({ title: "Announcement deleted" });
      setDeleteOpen(false);
      setActiveRow(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to delete announcement",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const onAdd = () => {
    setForm({ title: "", body: "", type: "text", is_active: true });
    setCreateOpen(true);
  };

  const onShow = async (id: string | number) => {
    try {
      setDetailsLoading(true);
      setDetailsOpen(true);
      const res = await adminApi.announcement(id);
      const root = (res as { data?: unknown })?.data ?? {};
      const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
      setDetailsData(row);
    } catch (e) {
      toast({
        title: "Failed to load announcement details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDetailsOpen(false);
    } finally {
      setDetailsLoading(false);
    }
  };

  const onEdit = (row: AnnouncementRow) => {
    setActiveRow(row);
    setForm({
      title: row.title === "—" ? "" : row.title,
      body: row.body === "—" ? "" : row.body,
      type: "text",
      is_active: row.is_active,
    });
    setEditOpen(true);
  };

  const onOpenDelete = (row: AnnouncementRow) => {
    setActiveRow(row);
    setDeleteOpen(true);
  };

  const submitCreate = () => {
    if (!form.title.trim()) {
      toast({ title: "Title is required", variant: "destructive" });
      return;
    }
    if (!form.body.trim()) {
      toast({ title: "Body is required", variant: "destructive" });
      return;
    }
    createMutation.mutate({
      title: form.title.trim(),
      body: form.body.trim(),
      type: form.type,
      is_active: form.is_active,
    });
  };

  const submitEdit = () => {
    if (!activeRow) return;
    if (!form.title.trim()) {
      toast({ title: "Title is required", variant: "destructive" });
      return;
    }
    if (!form.body.trim()) {
      toast({ title: "Body is required", variant: "destructive" });
      return;
    }
    updateMutation.mutate({
      id: activeRow.id,
      payload: {
        title: form.title.trim(),
        body: form.body.trim(),
        type: form.type,
      },
    });
  };

  const submitDelete = () => {
    if (!activeRow) return;
    deleteMutation.mutate(activeRow.id);
  };

  if (isLoading) return <div className="text-sm text-muted-foreground">Loading announcements...</div>;
  if (error) return <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load announcements"}</div>;

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <h2 className="text-2xl font-bold">Announcements</h2>
        <Button onClick={onAdd} className="gradient-primary text-primary-foreground border-0">Add Announcement</Button>
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Title</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Body</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {rows.map((row) => (
                <tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(row.id)}</td>
                  <td className="p-4">{row.title}</td>
                  <td className="p-4">{row.body}</td>
                  <td className="p-4">
                    <Badge variant="secondary" className={row.is_active ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}>
                      {row.is_active ? "active" : "inactive"}
                    </Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      <Button variant="outline" size="sm" onClick={() => onShow(row.id)}>Show</Button>
                      <Button variant="outline" size="sm" onClick={() => onEdit(row)}>Edit</Button>
                      <Button variant="outline" size="sm" onClick={() => statusMutation.mutate({ id: row.id, status: row.is_active ? "inactive" : "active" })}>
                        {row.is_active ? "Deactivate" : "Activate"}
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
            <DialogTitle>Add Announcement</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label htmlFor="announcement-create-title">Title *</Label>
              <Input
                id="announcement-create-title"
                value={form.title}
                onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="announcement-create-body">Body *</Label>
              <Textarea
                id="announcement-create-body"
                rows={4}
                value={form.body}
                onChange={(e) => setForm((prev) => ({ ...prev, body: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="announcement-create-type">Type</Label>
              <select
                id="announcement-create-type"
                title="Announcement type"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.type}
                onChange={(e) => setForm((prev) => ({ ...prev, type: e.target.value as "text" | "banner" }))}
              >
                <option value="text">text</option>
                <option value="banner">banner</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="announcement-create-status">Status</Label>
              <select
                id="announcement-create-status"
                title="Announcement status"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.is_active ? "active" : "inactive"}
                onChange={(e) => setForm((prev) => ({ ...prev, is_active: e.target.value === "active" }))}
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
            <DialogTitle>Edit Announcement</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label htmlFor="announcement-edit-title">Title *</Label>
              <Input
                id="announcement-edit-title"
                value={form.title}
                onChange={(e) => setForm((prev) => ({ ...prev, title: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="announcement-edit-body">Body *</Label>
              <Textarea
                id="announcement-edit-body"
                rows={4}
                value={form.body}
                onChange={(e) => setForm((prev) => ({ ...prev, body: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="announcement-edit-type">Type</Label>
              <select
                id="announcement-edit-type"
                title="Announcement type"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.type}
                onChange={(e) => setForm((prev) => ({ ...prev, type: e.target.value as "text" | "banner" }))}
              >
                <option value="text">text</option>
                <option value="banner">banner</option>
              </select>
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
            <DialogTitle>Delete Announcement</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">
            Delete announcement <span className="font-medium text-foreground">"{activeRow?.title ?? "—"}"</span>? This action cannot be undone.
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
            <DialogTitle>Announcement Details</DialogTitle>
          </DialogHeader>
          {detailsLoading ? (
            <div className="py-3 text-sm text-muted-foreground">Loading...</div>
          ) : (
            <div className="space-y-2 text-sm">
              <p><span className="font-medium">Title:</span> {String(detailsData?.title ?? "—")}</p>
              <p><span className="font-medium">Body:</span> {String(detailsData?.body ?? "—")}</p>
              <p><span className="font-medium">Type:</span> {String(detailsData?.type ?? "text")}</p>
              <p><span className="font-medium">Status:</span> {Boolean(detailsData?.is_active ?? true) ? "active" : "inactive"}</p>
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
