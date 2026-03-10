import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type Doctor = {
  id: string | number;
  name: string;
  email?: string | null;
  phone?: string | null;
  certifications?: string | null;
  specialty_id?: number | null;
  specialty_name?: string | null;
};

export default function ClinicDoctors() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    phone: "",
    certifications: "",
    specialty_id: "",
  });
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "doctors"],
    queryFn: () => clinicApi.doctors(),
  });
  const specialtiesQuery = useQuery({
    queryKey: ["clinic", "specialties"],
    queryFn: () => clinicApi.specialties(),
  });

  const doctors = useMemo<Doctor[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return Array.isArray(root) ? (root as Doctor[]) : [];
  }, [data]);
  const specialties = useMemo<Array<{ id: number | string; name?: string; name_en?: string; name_ar?: string }>>(() => {
    const root = (specialtiesQuery.data as { data?: unknown })?.data ?? specialtiesQuery.data;
    return Array.isArray(root) ? (root as Array<{ id: number | string; name?: string; name_en?: string; name_ar?: string }>) : [];
  }, [specialtiesQuery.data]);

  const filtered = doctors.filter((d) =>
    `${d.name ?? ""} ${d.email ?? ""} ${d.phone ?? ""} ${d.specialty_name ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const createMutation = useMutation({
    mutationFn: () =>
      clinicApi.createDoctor({
        name: form.name,
        email: form.email,
        password: form.password,
        phone: form.phone,
        certifications: form.certifications,
        specialty_id: Number(form.specialty_id),
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "doctors"] });
      toast({ title: "Doctor created" });
      setDialogMode(null);
    },
    onError: (e) => {
      toast({
        title: "Failed to create doctor",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: () =>
      clinicApi.updateDoctor(activeId, {
        name: form.name,
        email: form.email,
        ...(form.password ? { password: form.password } : {}),
        phone: form.phone,
        certifications: form.certifications,
        specialty_id: Number(form.specialty_id),
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "doctors"] });
      toast({ title: "Doctor updated" });
      setDialogMode(null);
    },
    onError: (e) => {
      toast({
        title: "Failed to update doctor",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({
      name: "",
      email: "",
      password: "",
      phone: "",
      certifications: "",
      specialty_id: "",
    });
  };

  const openShow = (doctor: Doctor) => {
    setDialogMode("show");
    setActiveId(String(doctor.id));
    setForm({
      name: doctor.name ?? "",
      email: doctor.email ?? "",
      password: "",
      phone: doctor.phone ?? "",
      certifications: doctor.certifications ?? "",
      specialty_id: doctor.specialty_id ? String(doctor.specialty_id) : "",
    });
  };

  const openEdit = (doctor: Doctor) => {
    setDialogMode("edit");
    setActiveId(String(doctor.id));
    setForm({
      name: doctor.name ?? "",
      email: doctor.email ?? "",
      password: "",
      phone: doctor.phone ?? "",
      certifications: doctor.certifications ?? "",
      specialty_id: doctor.specialty_id ? String(doctor.specialty_id) : "",
    });
  };

  const onSave = () => {
    if (!form.name.trim() || !form.email.trim() || !form.phone.trim() || !form.certifications.trim() || !form.specialty_id) {
      toast({ title: "Please fill all required fields", variant: "destructive" });
      return;
    }
    if (dialogMode === "add" && !form.password.trim()) {
      toast({ title: "Password is required for new doctor", variant: "destructive" });
      return;
    }

    if (dialogMode === "add") {
      createMutation.mutate();
    } else if (dialogMode === "edit") {
      updateMutation.mutate();
    }
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Doctors</h2>
          <p className="text-muted-foreground text-sm mt-1">Doctors table with add, show, and edit dialogs</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add
        </Button>
      </div>
      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-10"
          placeholder="Search doctors..."
        />
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Doctor Name</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Email</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Phone</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Certifications</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Specialization</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={7}>Loading doctors...</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={7}>
                    {error instanceof Error ? error.message : "Failed to load doctors"}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={7}>No doctors found.</td>
                </tr>
              )}
              {paged.map((d) => (
                <tr key={String(d.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(d.id)}</td>
                  <td className="p-4 font-medium">{d.name}</td>
                  <td className="p-4 text-muted-foreground">{d.email ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{d.phone ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{d.certifications ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{d.specialty_name ?? "—"}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(d)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(d)}>
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
            <DialogTitle>{dialogMode === "add" ? "Add Doctor" : dialogMode === "edit" ? "Edit Doctor" : "Doctor Details"}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Doctor Name *</Label>
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
                  placeholder={dialogMode === "edit" ? "Leave empty to keep current password" : ""}
                />
              </div>
              <div className="space-y-2">
                <Label>Phone *</Label>
                <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>

            <div className="space-y-2">
              <Label>Certifications *</Label>
              <Input
                value={form.certifications}
                onChange={(e) => setForm((f) => ({ ...f, certifications: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>

            <div className="space-y-2">
              <Label>Specializations *</Label>
              <Select
                value={form.specialty_id}
                onValueChange={(v) => setForm((f) => ({ ...f, specialty_id: v }))}
                disabled={dialogMode === "show"}
              >
                <SelectTrigger><SelectValue placeholder="Select specialization" /></SelectTrigger>
                <SelectContent>
                  {specialties.map((s) => (
                    <SelectItem key={String(s.id)} value={String(s.id)}>
                      {s.name ?? s.name_en ?? s.name_ar ?? `Specialty ${s.id}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
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
    </div>
  );
}





