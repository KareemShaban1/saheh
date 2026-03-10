import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, Plus, Edit, Eye, Trash2, UserCheck } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { labApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";
import { useToast } from "@/hooks/use-toast";


type UserRow = {
  id: string | number;
  name?: string;
  email?: string;
  phone?: string | null;
  job_title?: string | null;
  role?: string;
  role_id?: number | null;
  permissions_count?: number;
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

export default function LabUsers() {
  const { t } = useLanguage();
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
    permission_ids: [] as number[],
  });
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["lab", "users", page, perPage, search],
    queryFn: () =>
      labApi.users({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const rolesQuery = useQuery({
    queryKey: ["lab", "roles"],
    queryFn: () => labApi.roles(),
  });

  const permissionsQuery = useQuery({
    queryKey: ["lab", "permissions"],
    queryFn: () => labApi.permissions(),
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
      labApi.createUser({
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
        phone: form.phone.trim() || undefined,
        job_title: form.job_title.trim() || undefined,
        role_id: Number(form.role_id),
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "users"] });
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
      labApi.updateUser(activeId, {
        name: form.name.trim(),
        email: form.email.trim(),
        ...(form.password.trim() ? { password: form.password } : {}),
        phone: form.phone.trim() || undefined,
        job_title: form.job_title.trim() || undefined,
        role_id: Number(form.role_id),
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "users"] });
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

  const deactivateMutation = useMutation({
    mutationFn: () => labApi.deactivateUser(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "users"] });
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

  const restoreMutation = useMutation({
    mutationFn: (id: string | number) => labApi.restoreUser(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["lab", "users"] });
      toast({ title: "User activated" });
    },
    onError: (e) =>
      toast({
        title: "Failed to activate user",
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
      permission_ids: [],
    });
  };

  const openShow = async (row: UserRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    try {
      const details = await labApi.user(row.id);
      const root = (details as { data?: unknown })?.data ?? details;
      const userData = root as {
        name?: string;
        email?: string;
        phone?: string | null;
        job_title?: string | null;
        role_id?: number | null;
        permission_ids?: number[];
      };
      setForm({
        name: userData.name ?? row.name ?? "",
        email: userData.email ?? row.email ?? "",
        password: "",
        phone: userData.phone ?? row.phone ?? "",
        job_title: userData.job_title ?? row.job_title ?? "",
        role_id: userData.role_id ? String(userData.role_id) : row.role_id ? String(row.role_id) : "",
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
      const details = await labApi.user(row.id);
      const root = (details as { data?: unknown })?.data ?? details;
      const userData = root as {
        name?: string;
        email?: string;
        phone?: string | null;
        job_title?: string | null;
        role_id?: number | null;
        permission_ids?: number[];
      };
      setForm({
        name: userData.name ?? row.name ?? "",
        email: userData.email ?? row.email ?? "",
        password: "",
        phone: userData.phone ?? row.phone ?? "",
        job_title: userData.job_title ?? row.job_title ?? "",
        role_id: userData.role_id ? String(userData.role_id) : row.role_id ? String(row.role_id) : "",
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

  const onActivate = (row: UserRow) => {
    restoreMutation.mutate(row.id);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold"> {t("lab.users.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("lab.users.add")}
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("lab.users.search")}
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
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.email")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.phone")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.job")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.role")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.permissions")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.status")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.users.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={9}>{t("lab.users.loading")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={9}>{error instanceof Error ? error.message : "Failed to load users"}</td>
                </tr>
              )}
              {!isLoading && !error && users.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={9}>{t("lab.users.no_users_found")}</td>
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
                  <td className="p-4 text-muted-foreground">{u.permissions_count ?? 0}</td>
                  <td className="p-4">
                    <Badge variant={u.status === "active" ? "default" : "secondary"}>{u.status ?? "active"}</Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(u)}>
                        <Eye className="h-4 w-4" />
                        {t("lab.users.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(u)}>
                        <Edit className="h-4 w-4" />
                        {t("lab.users.edit")}
                      </Button>
                      {(u.status ?? "active") === "active" ? (
                        <Button variant="destructive" size="sm" className="gap-2" onClick={() => setDeleteId(String(u.id))}>
                          <Trash2 className="h-4 w-4" />
                          {t("lab.users.delete")}
                        </Button>
                      ) : (
                        <Button variant="outline" size="sm" className="gap-2" onClick={() => onActivate(u)} disabled={restoreMutation.isPending}>
                          <UserCheck className="h-4 w-4" />
                          {t("lab.users.activate")}
                        </Button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="flex items-center justify-between p-4 border-t">
          <p className="text-sm text-muted-foreground">
            {t("lab.users.page")} {pagination?.current_page ?? page} {t("lab.users.of")} {pagination?.last_page ?? 1}
            {typeof pagination?.total === "number" ? ` (${pagination.total} ${t("lab.users.total")})` : ""}
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
            <DialogTitle>{dialogMode === "add" ? t("lab.users.add") : dialogMode === "edit" ? t("lab.users.edit") : t("lab.users.details")}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("lab.users.name")} *</Label>
                <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("lab.users.email")} *</Label>
                <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("lab.users.password")} {dialogMode === "add" ? "*" : "(optional)"}</Label>
                <Input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                  disabled={dialogMode === "show"}
                  placeholder={dialogMode === "edit" ? t("lab.users.leave_empty_to_keep_current_password") : ""}
                />
              </div>
              <div className="space-y-2">
                <Label>{t("lab.users.phone")}</Label>
                <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("lab.users.job_title")}</Label>
                <Input value={form.job_title} onChange={(e) => setForm((f) => ({ ...f, job_title: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="lab-user-role">{t("lab.users.role")} *</Label>
                <select
                  id="lab-user-role"
                  title={t("lab.users.role")}
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.role_id}
                  onChange={(e) => setForm((f) => ({ ...f, role_id: e.target.value }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="">{t("lab.users.select_role")}</option>
                  {roles.map((role) => (
                    <option key={String(role.id)} value={String(role.id)}>
                      {role.name ?? `${t("lab.users.role")} ${role.id}`}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <div className="space-y-2">
              <Label>{t("lab.users.additional_permissions")} ({form.permission_ids.length})</Label>
              <div className="max-h-56 overflow-y-auto rounded-md border p-3 space-y-2">
                {permissions.map((permission) => {
                  const pid = Number(permission.id);
                  const checked = form.permission_ids.includes(pid);
                  return (
                    <label key={String(permission.id)} className="flex items-center gap-2 text-sm">
                      <Checkbox checked={checked} onCheckedChange={(state) => togglePermission(pid, Boolean(state))} disabled={dialogMode === "show"} />
                      <span>{permission.name ?? `${t("lab.users.permission")} ${permission.id}`}</span>
                    </label>
                  );
                })}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("lab.users.close")}</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? t("lab.users.saving") : t("lab.users.save")}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>{t("lab.users.delete_user")}?</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">{t("lab.users.delete_user_description")}</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleteId("")}>{t("lab.users.cancel")}</Button>
            <Button variant="destructive" onClick={() => deactivateMutation.mutate()} disabled={deactivateMutation.isPending}>
              {deactivateMutation.isPending ? t("lab.users.deleting") : t("lab.users.delete")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
