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
  remaining?: number;
  paid_amount?: number;
  report?: string | null;
  images?: string[];
  payment_history?: Array<{ date?: string; amount?: number; remaining?: number; payment_way?: string | null }>;
};

type PaymentRow = {
  date: string;
  amount: string;
  payment_way: "cash";
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
    report: "",
    payments: [] as PaymentRow[],
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
      report: "",
      payments: [],
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
        report: ray.report ?? "",
        payments: (ray.payment_history ?? []).map((p) => ({
          date: p.date ?? "",
          amount: String(p.amount ?? 0),
          payment_way: "cash",
        })),
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
        report: ray.report ?? "",
        payments: (ray.payment_history ?? []).map((p) => ({
          date: p.date ?? "",
          amount: String(p.amount ?? 0),
          payment_way: "cash",
        })),
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
                <th className="text-start font-medium p-4 text-muted-foreground">Paid</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Remaining</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={7}>Loading rays...</td></tr>
              )}
              {error && (
                <tr><td className="p-4 text-destructive" colSpan={7}>{error instanceof Error ? error.message : "Failed to load rays"}</td></tr>
              )}
              {!isLoading && !error && rays.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={7}>No rays found.</td></tr>
              )}
              {!isLoading && !error && rays.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.patient_name ?? `#${r.patient_id ?? "—"}`}</td>
                  <td className="p-4 text-muted-foreground">{r.date ?? "—"}</td>
                  <td className="p-4">
                    <Badge variant={r.payment === "paid" ? "default" : "secondary"}>{r.payment ?? "not_paid"}</Badge>
                  </td>
                  <td className="p-4 text-muted-foreground">{r.paid_amount ?? 0}</td>
                  <td className="p-4 text-muted-foreground">{r.remaining ?? 0}</td>
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
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Reservation ID (optional)</Label>
                <Input value={form.reservation_id} onChange={(e) => setForm((f) => ({ ...f, reservation_id: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Report</Label>
              <Textarea rows={3} value={form.report} onChange={(e) => setForm((f) => ({ ...f, report: e.target.value }))} disabled={dialogMode === "show"} />
            </div>
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label>Payment History</Label>
                {dialogMode !== "show" && (
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={() => setForm((f) => ({ ...f, payments: [...f.payments, { date: f.date || "", amount: "", payment_way: "cash" }] }))}
                  >
                    Add Payment
                  </Button>
                )}
              </div>
              {form.payments.length === 0 && <p className="text-sm text-muted-foreground">No payments.</p>}
              {form.payments.map((row, index) => (
                <div key={`payment-${index}`} className="grid grid-cols-12 gap-2 items-end border rounded-md p-2">
                  <div className="col-span-12 md:col-span-3">
                    <Label className="text-xs">Date</Label>
                    <Input type="date" value={row.date} onChange={(e) => setForm((f) => ({ ...f, payments: f.payments.map((p, i) => (i === index ? { ...p, date: e.target.value } : p)) }))} disabled={dialogMode === "show"} />
                  </div>
                  <div className="col-span-12 md:col-span-3">
                    <Label className="text-xs">Amount</Label>
                    <Input type="number" min="0" value={row.amount} onChange={(e) => setForm((f) => ({ ...f, payments: f.payments.map((p, i) => (i === index ? { ...p, amount: e.target.value } : p)) }))} disabled={dialogMode === "show"} />
                  </div>
                  <div className="col-span-12 md:col-span-3">
                    <Label className="text-xs">Remaining</Label>
                    <Input type="number" value={row.amount || "0"} readOnly disabled />
                  </div>
                  <div className="col-span-10 md:col-span-2">
                    <Label className="text-xs">Way</Label>
                    <select
                      title="Payment way"
                      className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                      value={row.payment_way}
                      onChange={(e) => setForm((f) => ({ ...f, payments: f.payments.map((p, i) => (i === index ? { ...p, payment_way: e.target.value as "cash" } : p)) }))}
                      disabled={dialogMode === "show"}
                    >
                      <option value="cash">cash</option>
                    </select>
                  </div>
                  {dialogMode !== "show" && (
                    <div className="col-span-2 md:col-span-1">
                      <Button type="button" size="icon" variant="ghost" onClick={() => setForm((f) => ({ ...f, payments: f.payments.filter((_, i) => i !== index) }))}>
                        <Trash2 className="h-4 w-4 text-destructive" />
                      </Button>
                    </div>
                  )}
                </div>
              ))}
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
  report: string;
  payments: PaymentRow[];
  images: File[];
}): FormData {
  const fd = new FormData();
  fd.append("patient_id", form.patient_id);
  if (form.reservation_id.trim()) fd.append("reservation_id", form.reservation_id.trim());
  fd.append("date", form.date);
  fd.append("report", form.report.trim());
  fd.append(
    "payments",
    JSON.stringify(
      form.payments
        .filter((row) => row.date && row.amount !== "")
        .map((row) => ({
          date: row.date,
          amount: Number(row.amount || 0),
          remaining: Number(row.amount || 0),
          payment_way: row.payment_way || undefined,
        })),
    ),
  );
  form.images.forEach((file) => fd.append("images[]", file));
  return fd;
}

