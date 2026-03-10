import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type RoleRow = { id: string | number; name?: string; permissions_count?: number };
type PermissionRow = { id: string | number; name?: string };

export default function ClinicRoles() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({ name: "", selectedPermissionIds: [] as number[] });
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "roles"],
    queryFn: () => clinicApi.roles(),
  });
  const permissionsQuery = useQuery({
    queryKey: ["clinic", "permissions"],
    queryFn: () => clinicApi.permissions(),
  });
  const roles = useMemo<RoleRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return Array.isArray(root) ? (root as RoleRow[]) : [];
  }, [data]);
  const permissions = useMemo<PermissionRow[]>(() => {
    const root = (permissionsQuery.data as { data?: unknown })?.data ?? permissionsQuery.data;
    return Array.isArray(root) ? (root as PermissionRow[]) : [];
  }, [permissionsQuery.data]);

  const filtered = roles.filter((r) => (r.name ?? "").toLowerCase().includes(search.toLowerCase()));
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const createMutation = useMutation({
    mutationFn: () => clinicApi.createRole({ name: form.name, permission_ids: form.selectedPermissionIds }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "roles"] });
      toast({ title: "Role created" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to create role",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () => clinicApi.updateRole(activeId, { name: form.name, permission_ids: form.selectedPermissionIds }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "roles"] });
      toast({ title: "Role updated" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update role",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ name: "", selectedPermissionIds: [] });
  };
  const openShow = async (row: RoleRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    const details = await clinicApi.role(row.id);
    const root = (details as { data?: unknown })?.data ?? details;
    const roleData = root as { name?: string; permission_ids?: number[] };
    setForm({ name: roleData.name ?? row.name ?? "", selectedPermissionIds: roleData.permission_ids ?? [] });
  };
  const openEdit = async (row: RoleRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    const details = await clinicApi.role(row.id);
    const root = (details as { data?: unknown })?.data ?? details;
    const roleData = root as { name?: string; permission_ids?: number[] };
    setForm({ name: roleData.name ?? row.name ?? "", selectedPermissionIds: roleData.permission_ids ?? [] });
  };
  const onSave = () => {
    if (!form.name.trim()) {
      toast({ title: "Role name is required", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      createMutation.mutate();
    } else if (dialogMode === "edit") {
      updateMutation.mutate();
    }
  };

  const togglePermission = (permissionId: number, checked: boolean) => {
    setForm((prev) => ({
      ...prev,
      selectedPermissionIds: checked
        ? [...prev.selectedPermissionIds, permissionId]
        : prev.selectedPermissionIds.filter((id) => id !== permissionId),
    }));
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Roles & Permissions</h2>
          <p className="text-muted-foreground text-sm mt-1">Table with add, show, and edit dialogs</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add Role
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search roles..." value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="pl-10" />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Role Name</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Permissions</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={4}>Loading roles...</td></tr>}
              {error && <tr><td className="p-4 text-destructive" colSpan={4}>{error instanceof Error ? error.message : "Failed to load roles"}</td></tr>}
              {!isLoading && !error && paged.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={4}>No roles found.</td></tr>}
              {paged.map((role) => (
                <tr key={String(role.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(role.id)}</td>
                  <td className="p-4 font-medium">{role.name ?? "—"}</td>
                  <td className="p-4"><Badge variant="outline">{role.permissions_count ?? 0}</Badge></td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(role)}><Eye className="h-4 w-4" />Show</Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(role)}><Edit className="h-4 w-4" />Edit</Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">Page {safePage} of {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>Previous</Button>
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={safePage >= totalPages}>Next</Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>{dialogMode === "add" ? "Add Role" : dialogMode === "edit" ? "Edit Role" : "Role Details"}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>Name</Label>
              <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} />
            </div>
            <div className="space-y-2">
              <Label>Permissions ({form.selectedPermissionIds.length})</Label>
              <div className="max-h-56 overflow-y-auto rounded-md border p-3 space-y-2">
                {permissions.map((permission) => {
                  const pid = Number(permission.id);
                  const checked = form.selectedPermissionIds.includes(pid);
                  return (
                    <label key={String(permission.id)} className="flex items-center gap-2 text-sm">
                      <Checkbox
                        checked={checked}
                        onCheckedChange={(state) => togglePermission(pid, Boolean(state))}
                        disabled={dialogMode === "show"}
                      />
                      <span>{permission.name ?? `Permission ${permission.id}`}</span>
                    </label>
                  );
                })}
              </div>
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
    </div>
  );
}
