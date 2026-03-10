import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type ServiceRow = {
  id: string | number;
  name?: string;
  category?: "main" | "sub" | string;
  price?: string;
  doctor_id?: number | null;
  doctor_name?: string | null;
  notes?: string | null;
  status?: string;
};

type DoctorOption = {
  id: number;
  name?: string;
};

export default function ClinicServices() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    service_name: "",
    doctor_id: "",
    type: "sub" as "main" | "sub",
    price: "0",
    notes: "",
  });
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "services"],
    queryFn: () => clinicApi.services(),
  });

  const { data: doctorsData } = useQuery({
    queryKey: ["clinic", "doctors"],
    queryFn: () => clinicApi.doctors(),
  });

  const services = useMemo<ServiceRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: ServiceRow[] })?.data ?? [];
  }, [data]);

  const doctors = useMemo<DoctorOption[]>(() => {
    const root = (doctorsData as { data?: unknown })?.data ?? doctorsData;
    return ((root as DoctorOption[]) ?? []).map((doctor) => ({
      id: doctor.id,
      name: doctor.name ?? "Unnamed doctor",
    }));
  }, [doctorsData]);

  const createMutation = useMutation({
    mutationFn: (payload: { service_name: string; doctor_id?: number | null; type: "main" | "sub"; price: number; notes?: string | null }) =>
      clinicApi.createService(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["clinic", "services"] });
      toast({ title: "Service added" });
      setDialogMode(null);
    },
    onError: (err: unknown) => {
      toast({ title: err instanceof Error ? err.message : "Failed to add service", variant: "destructive" });
    },
  });

  const updateMutation = useMutation({
    mutationFn: (args: { id: string; payload: { service_name: string; doctor_id?: number | null; type: "main" | "sub"; price: number; notes?: string | null } }) =>
      clinicApi.updateService(args.id, args.payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["clinic", "services"] });
      toast({ title: "Service updated" });
      setDialogMode(null);
    },
    onError: (err: unknown) => {
      toast({ title: err instanceof Error ? err.message : "Failed to update service", variant: "destructive" });
    },
  });

  const filtered = services.filter((s) =>
    `${s.name ?? ""} ${s.category ?? ""} ${s.doctor_name ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ service_name: "", doctor_id: "", type: "sub", price: "0", notes: "" });
  };
  const openShow = (s: ServiceRow) => {
    setDialogMode("show");
    setActiveId(String(s.id));
    setForm({
      service_name: s.name ?? "",
      doctor_id: s.doctor_id ? String(s.doctor_id) : "",
      type: s.category === "main" ? "main" : "sub",
      price: s.price ?? "0",
      notes: s.notes ?? "",
    });
  };
  const openEdit = (s: ServiceRow) => {
    setDialogMode("edit");
    setActiveId(String(s.id));
    setForm({
      service_name: s.name ?? "",
      doctor_id: s.doctor_id ? String(s.doctor_id) : "",
      type: s.category === "main" ? "main" : "sub",
      price: s.price ?? "0",
      notes: s.notes ?? "",
    });
  };

  const onSave = () => {
    if (!form.service_name.trim()) {
      toast({ title: "Service name is required", variant: "destructive" });
      return;
    }

    const parsedPrice = Number(form.price);
    if (Number.isNaN(parsedPrice) || parsedPrice < 0) {
      toast({ title: "Price must be a valid non-negative number", variant: "destructive" });
      return;
    }

    const payload = {
      service_name: form.service_name.trim(),
      doctor_id: form.doctor_id ? Number(form.doctor_id) : null,
      type: form.type,
      price: parsedPrice,
      notes: form.notes.trim() || null,
    };

    if (dialogMode === "add") {
      createMutation.mutate(payload);
    } else if (dialogMode === "edit") {
      updateMutation.mutate({ id: activeId, payload });
    }
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Services</h2>
          <p className="text-muted-foreground text-sm mt-1">Manage clinic services with doctor assignment</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2"><Plus className="h-4 w-4" />Add Service</Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search by name, type, or doctor..."
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
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
                <th className="text-start font-medium p-4 text-muted-foreground">Type</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Doctor</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Price</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={6}>Loading services...</td></tr>}
              {error && <tr><td className="p-4 text-destructive" colSpan={6}>{error instanceof Error ? error.message : "Failed to load services"}</td></tr>}
              {!isLoading && !error && paged.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={6}>No services found.</td></tr>}
              {paged.map((s) => (
                <tr key={String(s.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(s.id)}</td>
                  <td className="p-4 font-medium">{s.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground capitalize">{s.category ?? "sub"}</td>
                  <td className="p-4 text-muted-foreground">{s.doctor_name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{s.price ?? "0"} EGP</td>
                  <td className="p-4"><div className="flex gap-2"><Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(s)}><Eye className="h-4 w-4" />Show</Button><Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(s)}><Edit className="h-4 w-4" />Edit</Button></div></td>
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
          <DialogHeader><DialogTitle>{dialogMode === "add" ? "Add Service" : dialogMode === "edit" ? "Edit Service" : "Service Details"}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>Service Name</Label>
              <Input
                value={form.service_name}
                onChange={(e) => setForm((f) => ({ ...f, service_name: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label htmlFor="service-doctor">Doctor</Label>
                <select
                  id="service-doctor"
                  title="Doctor"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.doctor_id}
                  onChange={(e) => setForm((f) => ({ ...f, doctor_id: e.target.value }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="">Select doctor</option>
                  {doctors.map((doctor) => (
                    <option key={doctor.id} value={doctor.id}>
                      {doctor.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="service-type">Type</Label>
                <select
                  id="service-type"
                  title="Type"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.type}
                  onChange={(e) => setForm((f) => ({ ...f, type: e.target.value as "main" | "sub" }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="main">Main</option>
                  <option value="sub">Sub</option>
                </select>
              </div>
              <div className="space-y-2">
                <Label>Price</Label>
                <Input
                  type="number"
                  min="0"
                  step="0.01"
                  value={form.price}
                  onChange={(e) => setForm((f) => ({ ...f, price: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Notes</Label>
              <Textarea
                value={form.notes}
                onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
                disabled={dialogMode === "show"}
                rows={4}
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>
              Close
            </Button>
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

