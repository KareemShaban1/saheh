import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { BASE_URL, clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

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
  const { t } = useLanguage();
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

  const extractArray = (payload: unknown): Record<string, unknown>[] => {
    if (Array.isArray(payload)) return payload as Record<string, unknown>[];
    if (payload && typeof payload === "object") {
      const obj = payload as Record<string, unknown>;
      if (Array.isArray(obj.data)) return obj.data as Record<string, unknown>[];
      if (obj.data && typeof obj.data === "object") {
        const nested = obj.data as Record<string, unknown>;
        if (Array.isArray(nested.data)) return nested.data as Record<string, unknown>[];
      }
    }
    return [];
  };

  const specialtiesQuery = useQuery({
    queryKey: ["clinic", "specialties"],
    queryFn: async () => {
      try {
        return await clinicApi.specialties();
      } catch {
        const res = await fetch(`${BASE_URL.replace(/\/$/, "")}/public/specialties`);
        if (!res.ok) return { data: [] };
        return res.json();
      }
    },
  });

  const doctors = useMemo<Doctor[]>(() => {
    return extractArray(data).map((row) => ({
      id: String(row.id ?? "—"),
      name: String(row.name ?? "—"),
      email: row.email ? String(row.email) : null,
      phone: row.phone ? String(row.phone) : null,
      certifications: row.certifications ? String(row.certifications) : null,
      specialty_id: row.specialty_id ? Number(row.specialty_id) : null,
      specialty_name: row.specialty_name ? String(row.specialty_name) : null,
    }));
  }, [data]);
  const specialties = useMemo<Array<{ id: number | string; name?: string; name_en?: string; name_ar?: string }>>(() => {
    return extractArray(specialtiesQuery.data).map((row) => ({
      id: String(row.id ?? ""),
      name: row.name ? String(row.name) : undefined,
      name_en: row.name_en ? String(row.name_en) : undefined,
      name_ar: row.name_ar ? String(row.name_ar) : undefined,
    }));
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
      toast({ title: t("clinic.doctors.doctor_created") });
      setDialogMode(null);
    },
    onError: (e) => {
      toast({
        title: t("clinic.doctors.failed_to_create_doctor"),
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
      toast({ title: t("clinic.doctors.doctor_updated") });
      setDialogMode(null);
    },
    onError: (e) => {
      toast({
        title: t("clinic.doctors.failed_to_update_doctor"),
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
      toast({ title: t("clinic.doctors.please_fill_all_required_fields"), variant: "destructive" });
      return;
    }
    if (dialogMode === "add" && !form.password.trim()) {
      toast({ title: t("clinic.doctors.password_is_required_for_new_doctor"), variant: "destructive" });
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
          <h2 className="text-2xl font-bold"> {t("clinic.doctors.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          {t("clinic.doctors.add")}
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
          placeholder={t("clinic.doctors.search")}
        />
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.doctors.doctor_name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.doctors.email")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.doctors.phone")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.doctors.certifications")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.doctors.specialization")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.doctors.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={7}>{t("clinic.doctors.loading_doctors")}</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={7}>
                    {error instanceof Error ? error.message : t("clinic.doctors.failed_to_load_doctors")}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={7}>{t("clinic.doctors.no_doctors_found")}</td>
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
                        {t("clinic.doctors.show")}
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(d)}>
                        <Edit className="h-4 w-4" />
                        {t("clinic.doctors.edit")}
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
            <p className="text-sm text-muted-foreground">{t("clinic.doctors.page")} {safePage} {t("clinic.doctors.of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                {t("clinic.doctors.previous")}
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
              >
                {t("clinic.doctors.next")}
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>{dialogMode === "add" ? t("clinic.doctors.add") : dialogMode === "edit" ? t("clinic.doctors.edit") : t("clinic.doctors.details")}</DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.doctors.doctor_name")} *</Label>
                <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.doctors.email")} *</Label>
                <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>{t("clinic.doctors.password")} {dialogMode === "add" ? "*" : "(optional)"}</Label>
                <Input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                  disabled={dialogMode === "show"}
                  placeholder={dialogMode === "edit" ? t("clinic.doctors.leave_empty_to_keep_current_password") : ""}
                />
              </div>
              <div className="space-y-2">
                <Label>{t("clinic.doctors.phone")} *</Label>
                <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} disabled={dialogMode === "show"} />
              </div>
            </div>

            <div className="space-y-2">
              <Label>{t("clinic.doctors.certifications")} *</Label>
              <Input
                value={form.certifications}
                onChange={(e) => setForm((f) => ({ ...f, certifications: e.target.value }))}
                disabled={dialogMode === "show"}
              />
            </div>

            <div className="space-y-2">
              <Label>{t("clinic.doctors.specializations")} *</Label>
              <Select
                value={form.specialty_id}
                onValueChange={(v) => setForm((f) => ({ ...f, specialty_id: v }))}
                disabled={dialogMode === "show" || specialtiesQuery.isLoading}
              >
                <SelectTrigger><SelectValue placeholder={specialtiesQuery.isLoading ? t("clinic.doctors.loading_specializations") : t("clinic.doctors.select_specialization")} /></SelectTrigger>
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
            <Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.doctors.close")}</Button>
            {dialogMode !== "show" && (
              <Button onClick={onSave} disabled={createMutation.isPending || updateMutation.isPending}>
                {createMutation.isPending || updateMutation.isPending ? t("clinic.doctors.saving") : t("clinic.doctors.save")}
              </Button>
            )}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}





