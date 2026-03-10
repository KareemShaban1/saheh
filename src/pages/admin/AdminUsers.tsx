import CrudTable from "@/components/CrudTable";
import { useQuery } from "@tanstack/react-query";
import { adminApi } from "@/lib/api";

const columns = [
  { key: "id", label: "#" },
  { key: "name", label: "Name" },
  { key: "email", label: "Email" },
  { key: "role", label: "Role" },
  { key: "status", label: "Status" },
];

export default function AdminUsers() {
  const { data, isLoading, error } = useQuery({
    queryKey: ["admin", "users"],
    queryFn: () => adminApi.users({ per_page: "100" }),
  });

  const root = (data as { data?: unknown })?.data ?? data;
  const rows = ((root as { data?: Array<Record<string, unknown>> })?.data ?? []).map((user) => ({
    id: String(user.id ?? "—"),
    name: String(user.name ?? "—"),
    email: String(user.email ?? "—"),
    role: Array.isArray(user.roles) ? String((user.roles as string[]).join(", ") || "—") : "—",
    status: "active",
  }));

  if (isLoading) return <div className="text-sm text-muted-foreground">Loading users...</div>;
  if (error) return <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load users"}</div>;

  return <CrudTable title="Users & Roles" columns={columns} data={rows} />;
}
