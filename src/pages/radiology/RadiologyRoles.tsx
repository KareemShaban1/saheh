import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, Plus, Edit, Trash2, Shield, Eye } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { radiologyApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type Role = {
  id: string | number;
  name: string;
  permissions_count?: number;
  users_count?: number;
};

type Permission = {
  id: number;
  name: string;
};

const emptyForm = { name: "", permission_ids: [] as number[] };

export default function RadiologyRoles() {
  const queryClient = useQueryClient();
  const [modalMode, setModalMode] = useState<"create" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string | number | null>(null);
  const [search, setSearch] = useState("");
  const [form, setForm] = useState(emptyForm);
  const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);
  const { toast } = useToast();

  const rolesQuery = useQuery({
    queryKey: ["radiology", "roles"],
    queryFn: () => radiologyApi.roles(),
  });
  const permissionsQuery = useQuery({
    queryKey: ["radiology", "permissions"],
    queryFn: () => radiologyApi.permissions(),
  });

  const roles = useMemo<Role[]>(() => {
    const root = (rolesQuery.data as { data?: unknown })?.data ?? rolesQuery.data;
    return Array.isArray(root) ? (root as Role[]) : [];
  }, [rolesQuery.data]);

  const permissions = useMemo<Permission[]>(() => {
    const root = (permissionsQuery.data as { data?: unknown })?.data ?? permissionsQuery.data;
    return Array.isArray(root) ? (root as Permission[]) : [];
  }, [permissionsQuery.data]);

  const filtered = roles.filter((r) => r.name.toLowerCase().includes(search.toLowerCase()));

  const createMutation = useMutation({
    mutationFn: () =>
      radiologyApi.createRole({
        name: form.name.trim(),
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "roles"] });
      toast({ title: "Role created" });
      setModalMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to create role",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () => radiologyApi.updateRole(activeId as string, { name: form.name.trim(), permission_ids: form.permission_ids }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "roles"] });
      toast({ title: "Role updated" });
      setModalMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update role",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => radiologyApi.deleteRole(deleteConfirm as string),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "roles"] });
      toast({ title: "Role deleted" });
      setDeleteConfirm(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to delete role",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openCreate = () => {
    setActiveId(null);
    setForm(emptyForm);
    setModalMode("create");
  };
  const openShow = async (r: Role) => {
    setActiveId(r.id);
    const res = await radiologyApi.role(r.id);
    const root = (res as { data?: unknown })?.data ?? {};
    const details = (root && typeof root === "object" ? root : {}) as { name?: string; permission_ids?: number[] };
    setForm({
      name: String(details.name ?? r.name),
      permission_ids: Array.isArray(details.permission_ids) ? details.permission_ids : [],
    });
    setModalMode("show");
  };
  const openEdit = async (r: Role) => {
    setActiveId(r.id);
    const res = await radiologyApi.role(r.id);
    const root = (res as { data?: unknown })?.data ?? {};
    const details = (root && typeof root === "object" ? root : {}) as { name?: string; permission_ids?: number[] };
    setForm({
      name: String(details.name ?? r.name),
      permission_ids: Array.isArray(details.permission_ids) ? details.permission_ids : [],
    });
    setModalMode("edit");
  };

  const togglePerm = (p: number) => {
    setForm((f) => ({
      ...f,
      permission_ids: f.permission_ids.includes(p) ? f.permission_ids.filter((x) => x !== p) : [...f.permission_ids, p],
    }));
  };

  const save = () => {
    if (!form.name.trim()) {
      toast({ title: "Role name is required", variant: "destructive" });
      return;
    }
    if (modalMode === "edit") {
      updateMutation.mutate();
      return;
    }
    createMutation.mutate();
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">Roles & Permissions</h2>
        <Button onClick={openCreate} size="sm" className="gradient-primary text-primary-foreground border-0"><Plus className="h-4 w-4 mr-1" />Add Role</Button>
      </div>

      <div className="relative mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search roles..." value={search} onChange={e => setSearch(e.target.value)} className="pl-10 max-w-md" />
      </div>

      <div className="grid gap-4">
        {rolesQuery.isLoading ? <div className="text-sm text-muted-foreground">Loading roles...</div> : null}
        {rolesQuery.error ? <div className="text-sm text-destructive">{rolesQuery.error instanceof Error ? rolesQuery.error.message : "Failed to load roles"}</div> : null}
        {filtered.map(role => (
          <div key={role.id} className="bg-card rounded-xl border shadow-card p-5">
            <div className="flex items-start justify-between mb-3">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 rounded-lg bg-sidebar-accent flex items-center justify-center"><Shield className="h-5 w-5 text-primary" /></div>
                <div>
                  <h3 className="font-semibold">{role.name}</h3>
                  <p className="text-sm text-muted-foreground">{role.description}</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <Badge variant="outline">{role.users_count ?? 0} users</Badge>
                <Badge variant="outline">{role.permissions_count ?? 0} permissions</Badge>
                <button title="Show role" onClick={() => void openShow(role)} className="p-1.5 rounded hover:bg-muted"><Eye className="h-4 w-4 text-muted-foreground" /></button>
                <button title="Edit role" onClick={() => openEdit(role)} className="p-1.5 rounded hover:bg-muted"><Edit className="h-4 w-4 text-muted-foreground" /></button>
                <button title="Delete role" onClick={() => setDeleteConfirm(String(role.id))} className="p-1.5 rounded hover:bg-muted"><Trash2 className="h-4 w-4 text-destructive" /></button>
              </div>
            </div>
          </div>
        ))}
      </div>

      <Dialog open={modalMode !== null} onOpenChange={(open) => !open && setModalMode(null)}>
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
          <DialogHeader><DialogTitle>{modalMode === "create" ? "New Role" : modalMode === "edit" ? "Edit Role" : "Role Details"}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid gap-2"><Label>Role Name</Label><Input value={form.name} onChange={e => setForm({...form, name: e.target.value})} disabled={modalMode === "show"} /></div>

            <div>
              <div className="flex items-center justify-between mb-3">
                <Label className="text-base font-semibold">Permissions</Label>
                <Button variant="ghost" size="sm" onClick={() => setForm((f) => ({ ...f, permission_ids: f.permission_ids.length === permissions.length ? [] : permissions.map((p) => p.id) }))}>
                  {form.permission_ids.length === permissions.length ? "Deselect All" : "Select All"}
                </Button>
              </div>
              <div className="border rounded-lg p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                {permissions.map((p) => (
                  <div key={p.id} className="flex items-center gap-2">
                    <Checkbox
                      checked={form.permission_ids.includes(p.id)}
                      onCheckedChange={() => togglePerm(p.id)}
                      id={`perm-${p.id}`}
                      disabled={modalMode === "show"}
                    />
                    <label htmlFor={`perm-${p.id}`} className="text-sm text-muted-foreground cursor-pointer">
                      {p.name}
                    </label>
                  </div>
                ))}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setModalMode(null)}>Close</Button>
            {modalMode !== "show" ? (
              <Button onClick={save} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? "Saving..." : modalMode === "edit" ? "Update" : "Create"}
              </Button>
            ) : null}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={!!deleteConfirm} onOpenChange={() => setDeleteConfirm(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader><DialogTitle>Delete Role?</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">This will remove the role and its permissions.</p>
          <DialogFooter><Button variant="outline" onClick={() => setDeleteConfirm(null)}>Cancel</Button><Button variant="destructive" onClick={() => deleteMutation.mutate()} disabled={deleteMutation.isPending}>{deleteMutation.isPending ? "Deleting..." : "Delete"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
