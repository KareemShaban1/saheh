import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, Plus, Edit, Eye, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { radiologyApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type UserRow = {
  id: string | number;
  name?: string;
  email?: string;
  phone?: string | null;
  job_title?: string | null;
  role?: string;
  role_id?: number | null;
  status?: string;
};

type RoleRow = {
  id: string | number;
  name?: string;
};

type PermissionRow = {
  id: string | number;
  name?: string;
};

export default function RadiologyUsers() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [deleteId, setDeleteId] = useState<string>("");
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    phone: "",
    job_title: "",
    role_id: "",
    status: "active" as "active" | "inactive",
    permission_ids: [] as number[],
  });
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["radiology", "users", page, perPage, search],
    queryFn: () =>
      radiologyApi.users({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const rolesQuery = useQuery({
    queryKey: ["radiology", "roles"],
    queryFn: () => radiologyApi.roles(),
  });

  const permissionsQuery = useQuery({
    queryKey: ["radiology", "permissions"],
    queryFn: () => radiologyApi.permissions(),
  });

  const users = useMemo<UserRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return ((root as { data?: UserRow[] })?.data ?? []) as UserRow[];
  }, [data]);

  const pagination = useMemo(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;
  }, [data]);

  const roles = useMemo<RoleRow[]>(() => {
    const root = (rolesQuery.data as { data?: unknown })?.data ?? rolesQuery.data;
    return Array.isArray(root) ? (root as RoleRow[]) : [];
  }, [rolesQuery.data]);

  const permissions = useMemo<PermissionRow[]>(() => {
    const root = (permissionsQuery.data as { data?: unknown })?.data ?? permissionsQuery.data;
    return Array.isArray(root) ? (root as PermissionRow[]) : [];
  }, [permissionsQuery.data]);

  const createMutation = useMutation({
    mutationFn: () =>
      radiologyApi.createUser({
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
        phone: form.phone.trim() || undefined,
        job_title: form.job_title.trim() || undefined,
        role_id: Number(form.role_id),
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "users"] });
      toast({ title: "User created" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to create user",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () =>
      radiologyApi.updateUser(activeId, {
        name: form.name.trim(),
        email: form.email.trim(),
        ...(form.password.trim() ? { password: form.password } : {}),
        phone: form.phone.trim() || undefined,
        job_title: form.job_title.trim() || undefined,
        role_id: Number(form.role_id),
        status: form.status,
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "users"] });
      toast({ title: "User updated" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update user",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => radiologyApi.deleteUser(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "users"] });
      toast({ title: "User deleted" });
      setDeleteId("");
    },
    onError: (e) =>
      toast({
        title: "Failed to delete user",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({
      name: "",
      email: "",
      password: "",
      phone: "",
      job_title: "",
      role_id: "",
      status: "active",
      permission_ids: [],
    });
  };

  const openShow = async (row: UserRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    try {
      const details = await radiologyApi.user(row.id);
      const root = (details as { data?: unknown })?.data ?? details;
      const userData = root as {
        name?: string;
        email?: string;
        phone?: string | null;
        job_title?: string | null;
        role_id?: number | null;
        permission_ids?: number[];
        status?: "active" | "inactive";
      };
      setForm({
        name: userData.name ?? row.name ?? "",
        email: userData.email ?? row.email ?? "",
        password: "",
        phone: userData.phone ?? row.phone ?? "",
        job_title: userData.job_title ?? row.job_title ?? "",
        role_id: userData.role_id ? String(userData.role_id) : row.role_id ? String(row.role_id) : "",
        status: userData.status ?? ((row.status as "active" | "inactive") ?? "active"),
        permission_ids: userData.permission_ids ?? [],
      });
    } catch (e) {
      toast({
        title: "Failed to load user details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDialogMode(null);
    }
  };

  const openEdit = async (row: UserRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    try {
      const details = await radiologyApi.user(row.id);
      const root = (details as { data?: unknown })?.data ?? details;
      const userData = root as {
        name?: string;
        email?: string;
        phone?: string | null;
        job_title?: string | null;
        role_id?: number | null;
        permission_ids?: number[];
        status?: "active" | "inactive";
      };
      setForm({
        name: userData.name ?? row.name ?? "",
        email: userData.email ?? row.email ?? "",
        password: "",
        phone: userData.phone ?? row.phone ?? "",
        job_title: userData.job_title ?? row.job_title ?? "",
        role_id: userData.role_id ? String(userData.role_id) : row.role_id ? String(row.role_id) : "",
        status: userData.status ?? ((row.status as "active" | "inactive") ?? "active"),
        permission_ids: userData.permission_ids ?? [],
      });
    } catch (e) {
      toast({
        title: "Failed to load user details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDialogMode(null);
    }
  };

  const onSave = () => {
    if (!form.name.trim() || !form.email.trim() || !form.role_id) {
      toast({ title: "Name, email, and role are required", variant: "destructive" });
      return;
    }
    if (dialogMode === "add" && !form.password.trim()) {
      toast({ title: "Password is required for new user", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") createMutation.mutate();
    if (dialogMode === "edit") updateMutation.mutate();
  };

  const togglePermission = (permissionId: number, checked: boolean) => {
    setForm((prev) => ({
      ...prev,
      permission_ids: checked ? [...prev.permission_ids, permissionId] : prev.permission_ids.filter((id) => id !== permissionId),
    }));
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <h2 className="text-2xl font-bold">Users</h2>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add User
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search users..."
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
                <th className="text-start font-medium p-4 text-muted-foreground">Email</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Phone</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Job</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Role</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={8}>Loading users...</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={8}>{error instanceof Error ? error.message : "Failed to load users"}</td>
                </tr>
              )}
              {!isLoading && !error && users.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={8}>No users found.</td>
                </tr>
              )}
              {!isLoading && !error && users.map((u) => (
                <tr key={String(u.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(u.id)}</td>
                  <td className="p-4 font-medium">{u.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{u.email ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{u.phone ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{u.job_title ?? "—"}</td>
                  <td className="p-4"><Badge variant="outline">{u.role ?? "staff"}</Badge></td>
                  <td className="p-4"><Badge variant={u.status === "active" ? "default" : "secondary"}>{u.status ?? "active"}</Badge></td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(u)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(u)}>
                        <Edit className="h-4 w-4" />
                        Edit
                      </Button>
                      <Button variant="destructive" size="sm" className="gap-2" onClick={() => setDeleteId(String(u.id))}>
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
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>{dialogMode === "add" ? "Add User" : dialogMode === "edit" ? "Edit User" : "User Details"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Name *</Label>
                <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>Email *</Label>
                <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Password {dialogMode === "add" ? "*" : "(optional)"}</Label>
                <Input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>Phone</Label>
                <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Job Title</Label>
                <Input value={form.job_title} onChange={(e) => setForm((f) => ({ ...f, job_title: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="radiology-user-role">Role *</Label>
                <select
                  id="radiology-user-role"
                  title="Role"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.role_id}
                  onChange={(e) => setForm((f) => ({ ...f, role_id: e.target.value }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="">Select role</option>
                  {roles.map((role) => (
                    <option key={String(role.id)} value={String(role.id)}>
                      {role.name ?? `Role ${role.id}`}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            {dialogMode !== "add" ? (
              <div className="space-y-2">
                <Label>Status</Label>
                <select
                  title="User status"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.status}
                  onChange={(e) => setForm((f) => ({ ...f, status: e.target.value as "active" | "inactive" }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="active">active</option>
                  <option value="inactive">inactive</option>
                </select>
              </div>
            ) : null}
            <div className="space-y-2">
              <Label>Additional Permissions ({form.permission_ids.length})</Label>
              <div className="max-h-56 overflow-y-auto rounded-md border p-3 space-y-2">
                {permissions.map((permission) => {
                  const pid = Number(permission.id);
                  const checked = form.permission_ids.includes(pid);
                  return (
                    <label key={String(permission.id)} className="flex items-center gap-2 text-sm">
                      <Checkbox checked={checked} onCheckedChange={(state) => togglePermission(pid, Boolean(state))} disabled={dialogMode === "show"} />
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

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete User?</DialogTitle>
          </DialogHeader>
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

