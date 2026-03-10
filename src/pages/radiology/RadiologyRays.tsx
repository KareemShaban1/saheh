import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, Plus, Edit, Eye, Trash2 } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { radiologyApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type RayRow = {
  id: string | number;
  patient_id?: number;
  patient_name?: string;
  reservation_id?: number | null;
  date?: string;
  payment?: "paid" | "not_paid";
  cost?: string | null;
  report?: string | null;
  images?: string[];
};

type PatientOption = {
  id: string | number;
  name?: string;
};

export default function RadiologyRays() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [deleteId, setDeleteId] = useState<string>("");
  const [form, setForm] = useState({
    patient_id: "",
    reservation_id: "",
    date: "",
    payment: "not_paid" as "paid" | "not_paid",
    cost: "",
    report: "",
    images: [] as File[],
    existing_images: [] as string[],
  });
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["radiology", "rays", page, perPage, search],
    queryFn: () =>
      radiologyApi.rays({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const patientsQuery = useQuery({
    queryKey: ["radiology", "patients", "options"],
    queryFn: () => radiologyApi.patients({ per_page: "300" }),
  });

  const rays = useMemo<RayRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return ((root as { data?: RayRow[] })?.data ?? []) as RayRow[];
  }, [data]);

  const pagination = useMemo(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;
  }, [data]);

  const patients = useMemo<PatientOption[]>(() => {
    const root = (patientsQuery.data as { data?: unknown })?.data ?? patientsQuery.data;
    return ((root as { data?: PatientOption[] })?.data ?? []) as PatientOption[];
  }, [patientsQuery.data]);

  const createMutation = useMutation({
    mutationFn: () => radiologyApi.createRay(toRayFormData(form)),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "rays"] });
      toast({ title: "Ray created" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to create ray",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () => radiologyApi.updateRay(activeId, toRayFormData(form)),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "rays"] });
      toast({ title: "Ray updated" });
      setDialogMode(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to update ray",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: () => radiologyApi.deleteRay(deleteId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "rays"] });
      toast({ title: "Ray deleted" });
      setDeleteId("");
    },
    onError: (e) =>
      toast({
        title: "Failed to delete ray",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({
      patient_id: "",
      reservation_id: "",
      date: "",
      payment: "not_paid",
      cost: "",
      report: "",
      images: [],
      existing_images: [],
    });
  };

  const openShow = async (row: RayRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    try {
      const details = await radiologyApi.ray(row.id);
      const root = (details as { data?: unknown })?.data ?? details;
      const ray = root as RayRow;
      setForm({
        patient_id: ray.patient_id ? String(ray.patient_id) : "",
        reservation_id: ray.reservation_id ? String(ray.reservation_id) : "",
        date: ray.date ?? "",
        payment: ray.payment ?? "not_paid",
        cost: ray.cost ?? "",
        report: ray.report ?? "",
        images: [],
        existing_images: Array.isArray(ray.images) ? ray.images : [],
      });
    } catch (e) {
      toast({
        title: "Failed to load ray details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDialogMode(null);
    }
  };

  const openEdit = async (row: RayRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    try {
      const details = await radiologyApi.ray(row.id);
      const root = (details as { data?: unknown })?.data ?? details;
      const ray = root as RayRow;
      setForm({
        patient_id: ray.patient_id ? String(ray.patient_id) : "",
        reservation_id: ray.reservation_id ? String(ray.reservation_id) : "",
        date: ray.date ?? "",
        payment: ray.payment ?? "not_paid",
        cost: ray.cost ?? "",
        report: ray.report ?? "",
        images: [],
        existing_images: Array.isArray(ray.images) ? ray.images : [],
      });
    } catch (e) {
      toast({
        title: "Failed to load ray details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDialogMode(null);
    }
  };

  const onSave = () => {
    if (!form.patient_id || !form.date) {
      toast({ title: "Patient and date are required", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") createMutation.mutate();
    if (dialogMode === "edit") updateMutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <h2 className="text-2xl font-bold">Rays</h2>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add Ray
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search rays..."
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
                <th className="text-start font-medium p-4 text-muted-foreground">Patient</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Date</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Payment</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Cost</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={6}>Loading rays...</td></tr>
              )}
              {error && (
                <tr><td className="p-4 text-destructive" colSpan={6}>{error instanceof Error ? error.message : "Failed to load rays"}</td></tr>
              )}
              {!isLoading && !error && rays.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={6}>No rays found.</td></tr>
              )}
              {!isLoading && !error && rays.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.patient_name ?? `#${r.patient_id ?? "—"}`}</td>
                  <td className="p-4 text-muted-foreground">{r.date ?? "—"}</td>
                  <td className="p-4">
                    <Badge variant={r.payment === "paid" ? "default" : "secondary"}>{r.payment ?? "not_paid"}</Badge>
                  </td>
                  <td className="p-4 text-muted-foreground">{r.cost ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(r)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(r)}>
                        <Edit className="h-4 w-4" />
                        Edit
                      </Button>
                      <Button variant="destructive" size="sm" className="gap-2" onClick={() => setDeleteId(String(r.id))}>
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
            <DialogTitle>{dialogMode === "add" ? "Add Ray" : dialogMode === "edit" ? "Edit Ray" : "Ray Details"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="ray-patient">Patient *</Label>
                <select
                  id="ray-patient"
                  title="Patient"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.patient_id}
                  onChange={(e) => setForm((f) => ({ ...f, patient_id: e.target.value }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="">Select patient</option>
                  {patients.map((p) => (
                    <option key={String(p.id)} value={String(p.id)}>
                      {p.name ?? `Patient ${p.id}`}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label>Date *</Label>
                <Input type="date" value={form.date} onChange={(e) => setForm((f) => ({ ...f, date: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2">
                <Label>Reservation ID (optional)</Label>
                <Input value={form.reservation_id} onChange={(e) => setForm((f) => ({ ...f, reservation_id: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>Payment</Label>
                <select
                  title="Payment"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={form.payment}
                  onChange={(e) => setForm((f) => ({ ...f, payment: e.target.value as "paid" | "not_paid" }))}
                  disabled={dialogMode === "show"}
                >
                  <option value="not_paid">not_paid</option>
                  <option value="paid">paid</option>
                </select>
              </div>
              <div className="space-y-2">
                <Label>Cost (optional)</Label>
                <Input type="number" min="0" value={form.cost} onChange={(e) => setForm((f) => ({ ...f, cost: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Report</Label>
              <Textarea rows={3} value={form.report} onChange={(e) => setForm((f) => ({ ...f, report: e.target.value }))} disabled={dialogMode === "show"} />
            </div>
            <div className="space-y-2">
              <Label>Images</Label>
              {dialogMode === "show" ? (
                <div className="space-y-1">
                  {form.existing_images.length === 0 ? <p className="text-sm text-muted-foreground">No images</p> : null}
                  {form.existing_images.map((url, idx) => (
                    <a key={`${idx}-${url}`} href={url} target="_blank" rel="noreferrer" className="block text-sm text-primary underline">
                      View image {idx + 1}
                    </a>
                  ))}
                </div>
              ) : (
                <Input type="file" multiple accept="image/*" onChange={(e) => setForm((f) => ({ ...f, images: Array.from(e.target.files ?? []) }))} />
              )}
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>Close</Button>
            {dialogMode !== "show" ? (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? "Saving..." : "Save"}
              </Button>
            ) : null}
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(deleteId)} onOpenChange={(open) => !open && setDeleteId("")}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Delete Ray?</DialogTitle>
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

function toRayFormData(form: {
  patient_id: string;
  reservation_id: string;
  date: string;
  payment: "paid" | "not_paid";
  cost: string;
  report: string;
  images: File[];
}): FormData {
  const fd = new FormData();
  fd.append("patient_id", form.patient_id);
  if (form.reservation_id.trim()) fd.append("reservation_id", form.reservation_id.trim());
  fd.append("date", form.date);
  fd.append("payment", form.payment);
  if (form.cost.trim()) fd.append("cost", form.cost.trim());
  fd.append("report", form.report.trim());
  form.images.forEach((file) => fd.append("images[]", file));
  return fd;
}

