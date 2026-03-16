import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Plus, Search, UserCheck, UserX } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { withCachedFetch, withCachedQuery } from "@/lib/queryCache";
import { useLanguage } from "@/contexts/LanguageContext";

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

export default function ClinicUsers() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
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
  const { t } = useLanguage();
  const { data, isLoading, error } = useQuery({
    ...withCachedQuery({
      queryKey: ["clinic", "users", page, perPage, search],
      queryFn: () =>
      clinicApi.users({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
    }),
  });

  const rolesQuery = useQuery({
    ...withCachedQuery({
      queryKey: ["clinic", "roles"],
      queryFn: () => clinicApi.roles(),
    }),
  });

  const permissionsQuery = useQuery({
    ...withCachedQuery({
      queryKey: ["clinic", "permissions"],
      queryFn: () => clinicApi.permissions(),
    }),
  });

  const users = useMemo<UserRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: UserRow[] })?.data ?? [];
  }, [data]);

  const pagination = useMemo(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { pagination?: { current_page: number; last_page: number; total: number } })?.pagination;
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
      clinicApi.createUser({
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
        phone: form.phone.trim() || undefined,
        job_title: form.job_title.trim() || undefined,
        role_id: Number(form.role_id),
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "users"] });
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
      clinicApi.updateUser(activeId, {
        name: form.name.trim(),
        email: form.email.trim(),
        ...(form.password.trim() ? { password: form.password } : {}),
        phone: form.phone.trim() || undefined,
        job_title: form.job_title.trim() || undefined,
        role_id: Number(form.role_id),
        permission_ids: form.permission_ids,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "users"] });
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
    mutationFn: (id: string | number) => clinicApi.deactivateUser(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "users"] });
      toast({ title: "User deactivated" });
    },
    onError: (e) =>
      toast({
        title: "Failed to deactivate user",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const restoreMutation = useMutation({
    mutationFn: (id: string | number) => clinicApi.restoreUser(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "users"] });
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

  const openEdit = async (row: UserRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    const details = await queryClient.fetchQuery(
      withCachedFetch(["clinic", "users", "details", row.id], () => clinicApi.user(row.id)),
    );
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

    if (dialogMode === "add") {
      createMutation.mutate();
    } else if (dialogMode === "edit") {
      updateMutation.mutate();
    }
  };

  const togglePermission = (permissionId: number, checked: boolean) => {
    setForm((prev) => ({
      ...prev,
      permission_ids: checked
        ? [...prev.permission_ids, permissionId]
        : prev.permission_ids.filter((id) => id !== permissionId),
    }));
  };

  const onToggleStatus = (row: UserRow) => {
    const isActive = (row.status ?? "active") === "active";
    const action = isActive ? "deactivate" : "activate";
    const confirmed = window.confirm(`Are you sure you want to ${action} this user?`);
    if (!confirmed) return;

    if (isActive) {
      deactivateMutation.mutate(row.id);
    } else {
      restoreMutation.mutate(row.id);
    }
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold"> {t("clinic.users.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("clinic.users.add")}
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.users.search")}
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
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.email")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.phone")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.role")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.permissions")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.status")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.users.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={8}>{t("clinic.users.loading_users")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={8}>
                    {error instanceof Error ? error.message : t("clinic.users.failed_to_load_users")}
                  </td>
                </tr>
              )}
              {!isLoading && !error && users.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={8}>{t("clinic.users.no_users_found")}</td>
                </tr>
              )}
              {users.map((u, index) => (
                <tr key={String(u.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{ index + 1 }</td>
                  <td className="p-4 font-medium">{u.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{u.email ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{u.phone ?? "—"}</td>
                  <td className="p-4"><Badge variant="outline">{u.role ?? "staff"}</Badge></td>
                  <td className="p-4 text-muted-foreground">{u.permissions_count ?? 0}</td>
                  <td className="p-4">
                    <Badge variant={u.status === "active" ? "default" : "secondary"}>{u.status ?? "active"}</Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(u)}>
                        <Edit className="h-4 w-4" />
                        {t("clinic.users.edit")}
                      </Button>
                      <Button
                        variant={(u.status ?? "active") === "active" ? "destructive" : "outline"}
                        size="sm"
                        className="gap-2"
                        onClick={() => onToggleStatus(u)}
                        disabled={deactivateMutation.isPending || restoreMutation.isPending}
                      >
                        {(u.status ?? "active") === "active" ? (
                          <>
                            <UserX className="h-4 w-4" />
                            {t("clinic.users.deactivate")}
                          </>
                        ) : (
                          <>
                            <UserCheck className="h-4 w-4" />
                            {t("clinic.users.activate")}
                          </>
                        )}
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && (pagination?.last_page ?? 1) > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">
              Page {pagination?.current_page ?? page} of {pagination?.last_page ?? 1}
              {typeof pagination?.total === "number" ? ` (${pagination.total} ${t("clinic.users.users")})` : ""}
            </p>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={(pagination?.current_page ?? page) <= 1}
              >
                {t("clinic.users.previous")}
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(pagination?.last_page ?? p, p + 1))}
                disabled={(pagination?.current_page ?? page) >= (pagination?.last_page ?? 1)}
              >
                {t("clinic.users.next")}
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>{dialogMode === "add" ? t("clinic.users.add_user") : t("clinic.users.edit_user")}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.users.name")} *</Label>
                <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.users.email")} *</Label>
                <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.users.password")} {dialogMode === "add" ? "*" : "(optional)"}</Label>
                <Input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                  placeholder={dialogMode === "edit" ? t("clinic.users.leave_empty_to_keep_current_password") : ""}
                />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.users.phone")}</Label>
                <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} />
              </div>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.users.job_title")}</Label>
                <Input value={form.job_title} onChange={(e) => setForm((f) => ({ ...f, job_title: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="user-role">{t("clinic.users.role")} *</Label>
                <select
                  id="user-role"
                  title="Role"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.role_id}
                  onChange={(e) => setForm((f) => ({ ...f, role_id: e.target.value }))}
                >
                  <option value="">{t("clinic.users.select_role")}</option>
                  {roles.map((role) => (
                    <option key={String(role.id)} value={String(role.id)}>
                      {role.name ?? `Role ${role.id}`}
                    </option>
                  ))}
                </select>
              </div>
            </div>
            <div className="space-y-2">
              <Label>{t("clinic.users.direct_permissions")} ({form.permission_ids.length})</Label>
              <div className="max-h-56 overflow-y-auto rounded-md border p-3 space-y-2">
                {permissions.map((permission) => {
                  const pid = Number(permission.id);
                  const checked = form.permission_ids.includes(pid);
                  return (
                    <label key={String(permission.id)} className="flex items-center gap-2 text-sm">
                      <Checkbox checked={checked} onCheckedChange={(state) => togglePermission(pid, Boolean(state))} />
                      <span>{permission.name ?? `Permission ${permission.id}`}</span>
                    </label>
                  );
                })}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.users.close")}</Button>
            <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
              {createMutation.isPending || updateMutation.isPending ? t("clinic.users.saving") : t("clinic.users.save")}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

