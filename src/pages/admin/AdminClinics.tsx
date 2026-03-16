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

type ClinicRow = {
  id: string | number;
  name: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  website?: string | null;
  description?: string | null;
  status: "pending" | "approved" | "rejected";
  is_active: number;
  users_count?: number;
  doctors_count?: number;
  patients_count?: number;
};

export default function AdminClinics() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [createOpen, setCreateOpen] = useState(false);
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [statusModalOpen, setStatusModalOpen] = useState(false);
  const [activeClinicId, setActiveClinicId] = useState<string | number | null>(null);
  const [statusTarget, setStatusTarget] = useState<ClinicRow | null>(null);
  const [statusDraft, setStatusDraft] = useState<ClinicRow["status"]>("pending");
  const [form, setForm] = useState({
    name: "",
    email: "",
    phone: "",
    address: "",
    website: "",
    description: "",
    status: "approved" as ClinicRow["status"],
    is_active: true,
  });

  const { data, isLoading, error } = useQuery({
    queryKey: ["admin", "clinics"],
    queryFn: () => adminApi.clinics({ per_page: "100" }),
  });

  const rows = useMemo<ClinicRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return ((root as { data?: Array<Record<string, unknown>> })?.data ?? []).map((clinic) => ({
      id: String(clinic.id ?? "—"),
      name: String(clinic.name ?? "—"),
      status: String(clinic.status ?? "pending") as ClinicRow["status"],
      is_active: Number(clinic.is_active ?? 0),
      users_count: Number(clinic.users_count ?? 0),
      doctors_count: Number(clinic.doctors_count ?? 0),
      patients_count: Number(clinic.patients_count ?? 0),
    }));
  }, [data]);

  const detailsQuery = useQuery({
    queryKey: ["admin", "clinic", activeClinicId],
    queryFn: () => adminApi.clinic(String(activeClinicId)),
    enabled: detailsOpen && !!activeClinicId,
  });

  const createMutation = useMutation({
    mutationFn: () =>
      adminApi.createClinic({
        name: form.name.trim(),
        email: form.email.trim() || null,
        phone: form.phone.trim() || null,
        address: form.address.trim() || null,
        website: form.website.trim() || null,
        description: form.description.trim() || null,
        status: form.status,
        is_active: form.is_active,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "clinics"] });
      toast({ title: "Clinic created successfully" });
      setCreateOpen(false);
      setForm({ name: "", email: "", phone: "", address: "", website: "", description: "", status: "approved", is_active: true });
    },
    onError: (e) =>
      toast({
        title: "Failed to create clinic",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: ClinicRow["status"] }) =>
      adminApi.updateClinicStatus(id, { status }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "clinics"] });
      toast({ title: "Clinic status updated" });
      setStatusModalOpen(false);
      setStatusTarget(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update status",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const activeMutation = useMutation({
    mutationFn: ({ id, is_active }: { id: string | number; is_active: boolean }) =>
      adminApi.updateClinicStatus(id, { is_active }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "clinics"] });
      toast({ title: "Clinic activation updated" });
    },
    onError: (e) =>
      toast({
        title: "Failed to update activation",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteClinic(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "clinics"] });
      toast({ title: "Clinic deleted successfully" });
    },
    onError: (e) =>
      toast({
        title: "Failed to delete clinic",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openDetails = (id: string | number) => {
    setActiveClinicId(id);
    setDetailsOpen(true);
  };

  const openStatusModal = (row: ClinicRow) => {
    setStatusTarget(row);
    setStatusDraft(row.status);
    setStatusModalOpen(true);
  };

  const deleteClinic = (row: ClinicRow) => {
    const ok = window.confirm(`Delete clinic "${row.name}"? This cannot be undone.`);
    if (!ok) return;
    deleteMutation.mutate(row.id);
  };

  const createClinic = () => {
    if (!form.name.trim()) {
      toast({ title: "Clinic name is required", variant: "destructive" });
      return;
    }
    createMutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Clinics Management</h2>
        </div>
        <Button className="gradient-primary text-primary-foreground border-0" onClick={() => setCreateOpen(true)}>
          Add Clinic
        </Button>
      </div>

      {isLoading ? <div className="text-sm text-muted-foreground">Loading clinics...</div> : null}
      {error ? <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load clinics"}</div> : null}

      {!isLoading && !error && (
        <div className="bg-card rounded-xl border shadow-card overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b bg-muted/50">
                  <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Name</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Users</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Doctors</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Patients</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Is Active</th>
                  <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y">
                {rows.map((row) => (
                  <tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
                    <td className="p-4 text-muted-foreground">{String(row.id)}</td>
                    <td className="p-4 font-medium">{row.name}</td>
                    <td className="p-4">{row.users_count ?? 0}</td>
                    <td className="p-4">{row.doctors_count ?? 0}</td>
                    <td className="p-4">{row.patients_count ?? 0}</td>
                    <td className="p-4">
                      <Badge
                        variant="secondary"
                        className={
                          row.status === "approved"
                            ? "bg-success/10 text-success"
                            : row.status === "rejected"
                              ? "bg-destructive/10 text-destructive"
                              : "bg-warning/10 text-warning"
                        }
                      >
                        {row.status}
                      </Badge>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-3">
                        <label className="inline-flex items-center gap-1 text-xs">
                          <input
                            type="radio"
                            name={`is-active-${row.id}`}
                            checked={row.is_active === 1}
                            onChange={() => activeMutation.mutate({ id: row.id, is_active: true })}
                            disabled={activeMutation.isPending}
                          />
                          Active
                        </label>
                        <label className="inline-flex items-center gap-1 text-xs">
                          <input
                            type="radio"
                            name={`is-active-${row.id}`}
                            checked={row.is_active !== 1}
                            onChange={() => activeMutation.mutate({ id: row.id, is_active: false })}
                            disabled={activeMutation.isPending}
                          />
                          Inactive
                        </label>
                      </div>
                    </td>
                    <td className="p-4">
                      <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" onClick={() => openDetails(row.id)}>
                          Show
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => openStatusModal(row)} disabled={statusMutation.isPending}>
                          Change Status
                        </Button>
                        <Button variant="destructive" size="sm" onClick={() => deleteClinic(row)} disabled={deleteMutation.isPending}>
                          Delete
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
                {rows.length === 0 ? (
                  <tr>
                    <td className="p-4 text-muted-foreground" colSpan={8}>No clinics found.</td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </div>
      )}

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Add Clinic</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>Name *</Label>
              <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Email</Label>
                <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
              </div>
              <div className="space-y-2">
                <Label>Phone</Label>
                <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Address</Label>
              <Input value={form.address} onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))} />
            </div>
            <div className="space-y-2">
              <Label>Website</Label>
              <Input value={form.website} onChange={(e) => setForm((f) => ({ ...f, website: e.target.value }))} />
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} rows={3} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="clinic-status">Status</Label>
              <select
                id="clinic-status"
                title="Clinic status"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={form.status}
                onChange={(e) => setForm((f) => ({ ...f, status: e.target.value as ClinicRow["status"] }))}
              >
                <option value="pending">pending</option>
                <option value="approved">approved</option>
                <option value="rejected">rejected</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label>Is Active</Label>
              <div className="flex items-center gap-5">
                <label className="inline-flex items-center gap-2 text-sm">
                  <input
                    type="radio"
                    name="create-is-active"
                    checked={form.is_active}
                    onChange={() => setForm((f) => ({ ...f, is_active: true }))}
                  />
                  Active
                </label>
                <label className="inline-flex items-center gap-2 text-sm">
                  <input
                    type="radio"
                    name="create-is-active"
                    checked={!form.is_active}
                    onChange={() => setForm((f) => ({ ...f, is_active: false }))}
                  />
                  Inactive
                </label>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setCreateOpen(false)}>Cancel</Button>
            <Button onClick={createClinic} disabled={createMutation.isPending}>
              {createMutation.isPending ? "Saving..." : "Save"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={detailsOpen} onOpenChange={setDetailsOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Clinic Details</DialogTitle>
          </DialogHeader>
          {detailsQuery.isLoading ? (
            <div className="text-sm text-muted-foreground py-4">Loading...</div>
          ) : detailsQuery.error ? (
            <div className="text-sm text-destructive py-4">{detailsQuery.error instanceof Error ? detailsQuery.error.message : "Failed to load details"}</div>
          ) : (
            <div className="space-y-2 text-sm">
              {(() => {
                const root = (detailsQuery.data as { data?: unknown })?.data ?? detailsQuery.data;
                const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
                return (
                  <>
                    <p><span className="font-medium">Name:</span> {String(row.name ?? "—")}</p>
                    <p><span className="font-medium">Email:</span> {String(row.email ?? "—")}</p>
                    <p><span className="font-medium">Phone:</span> {String(row.phone ?? "—")}</p>
                    <p><span className="font-medium">Address:</span> {String(row.address ?? "—")}</p>
                    <p><span className="font-medium">Website:</span> {String(row.website ?? "—")}</p>
                    <p><span className="font-medium">Description:</span> {String(row.description ?? "—")}</p>
                    <p><span className="font-medium">Status:</span> {String(row.status ?? "pending")}</p>
                    <p><span className="font-medium">Is Active:</span> {Number(row.is_active ?? 0) === 1 ? "active" : "inactive"}</p>
                    <p><span className="font-medium">Users:</span> {String(row.users_count ?? 0)}</p>
                    <p><span className="font-medium">Doctors:</span> {String(row.doctors_count ?? 0)}</p>
                    <p><span className="font-medium">Patients:</span> {String(row.patients_count ?? 0)}</p>
                  </>
                );
              })()}
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setDetailsOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={statusModalOpen} onOpenChange={setStatusModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Change Clinic Status</DialogTitle>
          </DialogHeader>
          <div className="space-y-2">
            <Label htmlFor="change-clinic-status">Status</Label>
            <select
              id="change-clinic-status"
              title="Change clinic status"
              className="w-full rounded-md border bg-background px-3 py-2 text-sm"
              value={statusDraft}
              onChange={(e) => setStatusDraft(e.target.value as ClinicRow["status"])}
            >
              <option value="pending">pending</option>
              <option value="approved">approved</option>
              <option value="rejected">rejected</option>
            </select>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setStatusModalOpen(false)}>Cancel</Button>
            <Button
              onClick={() => {
                if (!statusTarget) return;
                statusMutation.mutate({ id: statusTarget.id, status: statusDraft });
              }}
              disabled={statusMutation.isPending || !statusTarget}
            >
              {statusMutation.isPending ? "Saving..." : "Save"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
