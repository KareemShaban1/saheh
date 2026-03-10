import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type ModuleRow = {
  id: string | number;
  name?: string;
  key?: string;
  description?: string;
  billing?: string;
  status?: string;
  services_count?: number;
};

export default function ClinicModules() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [rows, setRows] = useState<ModuleRow[]>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    name: "",
    key: "",
    description: "",
    billing: "Included",
    status: "enabled",
    services_count: "0",
  });
  const { toast } = useToast();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "modules"],
    queryFn: () => clinicApi.modules(),
  });
  const modules = useMemo<ModuleRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return Array.isArray(root) ? (root as ModuleRow[]) : [];
  }, [data]);

  useEffect(() => {
    setRows(modules);
  }, [modules]);

  const filtered = rows.filter((m) => `${m.name ?? ""} ${m.key ?? ""}`.toLowerCase().includes(search.toLowerCase()));
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({
      name: "",
      key: "",
      description: "",
      billing: "Included",
      status: "enabled",
      services_count: "0",
    });
  };

  const openShow = (row: ModuleRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    setForm({
      name: row.name ?? "",
      key: row.key ?? "",
      description: row.description ?? "",
      billing: row.billing ?? "Included",
      status: row.status ?? "enabled",
      services_count: String(row.services_count ?? 0),
    });
  };

  const openEdit = (row: ModuleRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    setForm({
      name: row.name ?? "",
      key: row.key ?? "",
      description: row.description ?? "",
      billing: row.billing ?? "Included",
      status: row.status ?? "enabled",
      services_count: String(row.services_count ?? 0),
    });
  };

  const onSave = () => {
    if (!form.name || !form.key) {
      toast({ title: "Missing required fields", description: "Name and key are required.", variant: "destructive" });
      return;
    }

    if (dialogMode === "add") {
      setRows((prev) => [
        {
          id: `local-${Date.now()}`,
          name: form.name,
          key: form.key,
          description: form.description,
          billing: form.billing,
          status: form.status,
          services_count: Number(form.services_count || 0),
        },
        ...prev,
      ]);
      toast({ title: "Module added", description: "Saved in dashboard view." });
    } else if (dialogMode === "edit") {
      setRows((prev) =>
        prev.map((m) =>
          String(m.id) === activeId
            ? {
                ...m,
                name: form.name,
                key: form.key,
                description: form.description,
                billing: form.billing,
                status: form.status,
                services_count: Number(form.services_count || 0),
              }
            : m,
        ),
      );
      toast({ title: "Module updated", description: "Saved in dashboard view." });
    }

    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Modules</h2>
          <p className="text-muted-foreground text-sm mt-1">Modules table with add, show, and edit dialogs</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add Module
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search modules..."
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
                <th className="text-start font-medium p-4 text-muted-foreground">Key</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Services</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={6}>Loading modules...</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={6}>
                    {error instanceof Error ? error.message : "Failed to load modules"}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={6}>No modules found.</td>
                </tr>
              )}
              {paged.map((m) => (
                <tr key={String(m.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(m.id)}</td>
                  <td className="p-4 font-medium">{m.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{m.key ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{m.services_count ?? 0}</td>
                  <td className="p-4">
                    <Badge variant="outline">{m.status ?? "disabled"}</Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(m)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(m)}>
                        <Edit className="h-4 w-4" />
                        Edit
                      </Button>
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
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>
              {dialogMode === "add" ? "Add Module" : dialogMode === "edit" ? "Edit Module" : "Module Details"}
            </DialogTitle>
          </DialogHeader>

          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Name</Label>
                <Input
                  value={form.name}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>Key</Label>
                <Input
                  value={form.key}
                  onChange={(e) => setForm((prev) => ({ ...prev, key: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                rows={3}
                value={form.description}
                onChange={(e) => setForm((prev) => ({ ...prev, description: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>

            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>Billing</Label>
                <Input
                  value={form.billing}
                  onChange={(e) => setForm((prev) => ({ ...prev, billing: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>Status</Label>
                <Select
                  value={form.status}
                  onValueChange={(v) => setForm((prev) => ({ ...prev, status: v }))}
                  disabled={dialogMode === "show"}
                >
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="enabled">enabled</SelectItem>
                    <SelectItem value="disabled">disabled</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Services Count</Label>
                <Input
                  type="number"
                  value={form.services_count}
                  onChange={(e) => setForm((prev) => ({ ...prev, services_count: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>Close</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} className="gradient-primary text-primary-foreground border-0">
                Save
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

