import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Star, Plus, Edit, Trash2, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

interface Review {
  id: string | number;
  entityName: string;
  entityType: "clinic" | "doctor" | "lab" | "radiology";
  rating: number;
  comment: string;
  date?: string;
  status: "published" | "pending" | "rejected";
  organization_type?: string;
  organization_id?: number;
  doctor_id?: number | null;
}

const statusStyles: Record<string, string> = {
  published: "bg-success/10 text-success border-success/20",
  pending: "bg-warning/10 text-warning border-warning/20",
  rejected: "bg-destructive/10 text-destructive border-destructive/20",
};

const typeStyles: Record<string, string> = {
  clinic: "bg-primary/10 text-primary border-primary/20",
  doctor: "bg-secondary/10 text-secondary border-secondary/20",
  lab: "bg-accent/10 text-accent border-accent/20",
  radiology: "bg-warning/10 text-warning border-warning/20",
};

function StarRating({ rating, onChange }: { rating: number; onChange?: (r: number) => void }) {
  return (
    <div className="flex gap-0.5">
      {[1, 2, 3, 4, 5].map(s => (
        <Star
          key={s}
          className={`h-4 w-4 ${s <= rating ? "fill-warning text-warning" : "text-muted-foreground/30"} ${onChange ? "cursor-pointer" : ""}`}
          onClick={() => onChange?.(s)}
        />
      ))}
    </div>
  );
}

export default function PatientReviews() {
  const { token } = useAuth();
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [search, setSearch] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Review | null>(null);
  const [form, setForm] = useState({
    entityType: "clinic" as "clinic" | "doctor" | "lab" | "radiology",
    organizationId: "",
    doctorId: "",
    rating: 5,
    comment: "",
  });

  const reviewsQuery = useQuery({
    queryKey: ["patient", "reviews", token],
    queryFn: () => patientApi.reviews(token!),
    enabled: Boolean(token),
  });
  const clinicsQuery = useQuery({
    queryKey: ["patient", "clinics", "reviews", token],
    queryFn: () => patientApi.clinics(token!),
    enabled: Boolean(token),
  });
  const labsQuery = useQuery({
    queryKey: ["patient", "labs", "reviews", token],
    queryFn: () => patientApi.medicalLabs(token!),
    enabled: Boolean(token),
  });
  const radiologyQuery = useQuery({
    queryKey: ["patient", "radiology", "reviews", token],
    queryFn: () => patientApi.radiologyCenters(token!),
    enabled: Boolean(token),
  });
  const doctorsQuery = useQuery({
    queryKey: ["patient", "doctors", "reviews", token, form.organizationId],
    queryFn: () => patientApi.doctors(token!, form.organizationId ? { clinic_id: form.organizationId } : undefined),
    enabled: Boolean(token) && form.entityType === "doctor" && Boolean(form.organizationId),
  });

  const clinicsRaw = (clinicsQuery.data as { data?: unknown })?.data ?? clinicsQuery.data;
  const labsRaw = (labsQuery.data as { data?: unknown })?.data ?? labsQuery.data;
  const radiologyRaw = (radiologyQuery.data as { data?: unknown })?.data ?? radiologyQuery.data;
  const doctorsRaw = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
  const clinics = (Array.isArray(clinicsRaw) ? clinicsRaw : ((clinicsRaw as { data?: unknown[] })?.data ?? [])) as Array<Record<string, unknown>>;
  const labs = (Array.isArray(labsRaw) ? labsRaw : ((labsRaw as { data?: unknown[] })?.data ?? [])) as Array<Record<string, unknown>>;
  const radiology = (Array.isArray(radiologyRaw) ? radiologyRaw : ((radiologyRaw as { data?: unknown[] })?.data ?? [])) as Array<Record<string, unknown>>;
  const doctors = (Array.isArray(doctorsRaw) ? doctorsRaw : ((doctorsRaw as { data?: unknown[] })?.data ?? [])) as Array<Record<string, unknown>>;

  const rows = useMemo<Review[]>(() => {
    const raw = (reviewsQuery.data as { data?: unknown })?.data ?? reviewsQuery.data;
    const items = (Array.isArray(raw) ? raw : ((raw as { data?: unknown[] })?.data ?? [])) as Array<Record<string, unknown>>;

    const clinicMap = new Map(clinics.map((c) => [Number(c.id), String(c.name ?? `Clinic ${c.id}`)]));
    const labMap = new Map(labs.map((c) => [Number(c.id), String(c.name ?? `Lab ${c.id}`)]));
    const radioMap = new Map(radiology.map((c) => [Number(c.id), String(c.name ?? `Radiology ${c.id}`)]));
    const doctorMap = new Map(doctors.map((d) => [Number(d.id), String(d.name ?? `Doctor ${d.id}`)]));

    return items.map((r) => {
      const orgType = String(r.organization_type ?? "");
      const orgId = Number(r.organization_id ?? 0);
      const doctorId = r.doctor_id ? Number(r.doctor_id) : null;
      const entityType: Review["entityType"] =
        doctorId ? "doctor" : orgType.includes("Clinic") ? "clinic" : orgType.includes("MedicalLaboratory") ? "lab" : "radiology";
      const entityName =
        doctorId ? (doctorMap.get(doctorId) ?? `Doctor #${doctorId}`) :
        entityType === "clinic" ? (clinicMap.get(orgId) ?? `Clinic #${orgId}`) :
        entityType === "lab" ? (labMap.get(orgId) ?? `Lab #${orgId}`) :
        (radioMap.get(orgId) ?? `Radiology #${orgId}`);

      return {
        id: String(r.id ?? ""),
        entityName,
        entityType,
        rating: Number(r.rating ?? 0),
        comment: String(r.comment ?? ""),
        date: String(r.created_at ?? ""),
        status: Number(r.is_active ?? 1) === 1 ? "published" : "pending",
        organization_type: orgType,
        organization_id: orgId || undefined,
        doctor_id: doctorId,
      };
    });
  }, [reviewsQuery.data, clinics, labs, radiology, doctors]);

  const filtered = rows.filter((r) => r.entityName.toLowerCase().includes(search.toLowerCase()));

  const createMutation = useMutation({
    mutationFn: () =>
      patientApi.postReview(token!, {
        organization_type:
          form.entityType === "clinic"
            ? "App\\Models\\Clinic"
            : form.entityType === "lab"
              ? "App\\Models\\MedicalLaboratory"
              : "App\\Models\\RadiologyCenter",
        organization_id: Number(form.organizationId),
        doctor_id: form.entityType === "doctor" && form.doctorId ? Number(form.doctorId) : undefined,
        rating: form.rating,
        comment: form.comment.trim(),
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["patient", "reviews"] });
      toast({ title: "Review created" });
      setModalOpen(false);
    },
    onError: (e) =>
      toast({
        title: "Failed to create review",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const updateMutation = useMutation({
    mutationFn: () => patientApi.updateReview(token!, String(editing?.id), { rating: form.rating, comment: form.comment.trim() }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["patient", "reviews"] });
      toast({ title: "Review updated" });
      setModalOpen(false);
    },
    onError: (e) =>
      toast({
        title: "Failed to update review",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: string | number) => patientApi.deleteReview(token!, String(id)),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["patient", "reviews"] });
      toast({ title: "Review deleted" });
    },
    onError: (e) =>
      toast({
        title: "Failed to delete review",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openAdd = () => {
    setEditing(null);
    setForm({ entityType: "clinic", organizationId: "", doctorId: "", rating: 5, comment: "" });
    setModalOpen(true);
  };
  const openEdit = (r: Review) => {
    setEditing(r);
    setForm({
      entityType: r.entityType,
      organizationId: r.organization_id ? String(r.organization_id) : "",
      doctorId: r.doctor_id ? String(r.doctor_id) : "",
      rating: r.rating,
      comment: r.comment,
    });
    setModalOpen(true);
  };

  const save = () => {
    if (!form.comment.trim()) {
      toast({ title: "Comment is required", variant: "destructive" });
      return;
    }
    if (!editing && !form.organizationId) {
      toast({ title: "Select organization", variant: "destructive" });
      return;
    }
    if (editing) {
      updateMutation.mutate();
      return;
    }
    createMutation.mutate();
  };

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl font-bold">My Reviews</h2>
          <p className="text-muted-foreground text-sm mt-1">Manage your clinic and doctor reviews</p>
        </div>
        <div className="flex gap-2">
          <div className="relative w-full sm:w-52">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input placeholder="Search..." value={search} onChange={e => setSearch(e.target.value)} className="pl-9" />
          </div>
          <Button size="sm" className="gap-2 gradient-primary text-primary-foreground border-0" onClick={openAdd}>
            <Plus className="h-4 w-4" /> Add
          </Button>
        </div>
      </div>

      <div className="space-y-3">
        {reviewsQuery.isLoading && <div className="text-sm text-muted-foreground">Loading reviews...</div>}
        {reviewsQuery.error && <div className="text-sm text-destructive">{reviewsQuery.error instanceof Error ? reviewsQuery.error.message : "Failed to load reviews"}</div>}
        {!reviewsQuery.isLoading && !reviewsQuery.error && filtered.length === 0 && <div className="text-sm text-muted-foreground">No reviews found.</div>}
        {filtered.map((r) => (
          <div key={r.id} className="bg-card rounded-xl border p-5">
            <div className="flex items-start justify-between mb-2">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <h3 className="font-semibold">{r.entityName}</h3>
                  <Badge variant="outline" className={typeStyles[r.entityType]}>{r.entityType}</Badge>
                  <Badge variant="outline" className={statusStyles[r.status]}>{r.status}</Badge>
                </div>
                <div className="flex items-center gap-2">
                  <StarRating rating={r.rating} />
                  <span className="text-xs text-muted-foreground">{r.date}</span>
                </div>
              </div>
              <div className="flex gap-1">
                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => openEdit(r)}><Edit className="h-3.5 w-3.5" /></Button>
                <Button variant="ghost" size="icon" className="h-8 w-8 text-destructive" onClick={() => deleteMutation.mutate(r.id)}><Trash2 className="h-3.5 w-3.5" /></Button>
              </div>
            </div>
            <p className="text-sm text-muted-foreground">{r.comment}</p>
          </div>
        ))}
      </div>

      <Dialog open={modalOpen} onOpenChange={setModalOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{editing ? "Edit" : "Add"} Review</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            {!editing ? (
              <>
                <div><Label>Type</Label><Select value={form.entityType} onValueChange={v => setForm(f => ({ ...f, entityType: v as "clinic" | "doctor" | "lab" | "radiology", organizationId: "", doctorId: "" }))}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="clinic">Clinic</SelectItem><SelectItem value="doctor">Doctor</SelectItem><SelectItem value="lab">Lab</SelectItem><SelectItem value="radiology">Radiology</SelectItem></SelectContent></Select></div>
                <div>
                  <Label>Organization</Label>
                  <Select value={form.organizationId} onValueChange={(v) => setForm((f) => ({ ...f, organizationId: v, doctorId: "" }))}>
                    <SelectTrigger><SelectValue placeholder="Select organization" /></SelectTrigger>
                    <SelectContent>
                      {(form.entityType === "clinic" ? clinics : form.entityType === "lab" ? labs : radiology).map((row) => (
                        <SelectItem key={String(row.id)} value={String(row.id)}>{String(row.name ?? row.id)}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                {form.entityType === "doctor" ? (
                  <div>
                    <Label>Doctor</Label>
                    <Select value={form.doctorId} onValueChange={(v) => setForm((f) => ({ ...f, doctorId: v }))}>
                      <SelectTrigger><SelectValue placeholder="Select doctor (optional)" /></SelectTrigger>
                      <SelectContent>
                        {doctors.map((row) => (
                          <SelectItem key={String(row.id)} value={String(row.id)}>{String(row.name ?? row.id)}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                ) : null}
              </>
            ) : null}
            <div><Label>Rating</Label><StarRating rating={form.rating} onChange={r => setForm(f => ({ ...f, rating: r }))} /></div>
            <div><Label>Comment</Label><Textarea value={form.comment} onChange={e => setForm(f => ({ ...f, comment: e.target.value }))} rows={3} /></div>
          </div>
          <DialogFooter><Button onClick={save} disabled={createMutation.isPending || updateMutation.isPending} className="gradient-primary text-primary-foreground border-0">{editing ? "Update" : "Submit"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
