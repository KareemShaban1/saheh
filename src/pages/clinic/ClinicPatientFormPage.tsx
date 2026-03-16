import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Link, useNavigate, useParams } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

type FormState = {
  doctor_ids: string[];
  name: string;
  address: string;
  email: string;
  password: string;
  phone: string;
  whatsapp_number: string;
  age: string;
  gender: "male" | "female";
  blood_group: "" | "A+" | "A-" | "B+" | "B-" | "O+" | "O-" | "AB+" | "AB-";
  height: string;
  weight: string;
};

const INITIAL_FORM: FormState = {
  doctor_ids: [],
  name: "",
  address: "",
  email: "",
  password: "",
  phone: "",
  whatsapp_number: "",
  age: "",
  gender: "male",
  blood_group: "",
  height: "",
  weight: "",
};

export default function ClinicPatientFormPage() {
  const { id } = useParams<{ id: string }>();
  const isEdit = Boolean(id);
  const navigate = useNavigate();
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [form, setForm] = useState<FormState>(INITIAL_FORM);
  const { t } = useLanguage();
  const doctorsQuery = useQuery({
    queryKey: ["clinic", "doctors", "for-patient-form"],
    queryFn: () => clinicApi.doctors(),
  });
  const detailsQuery = useQuery({
    queryKey: ["clinic", "patient", id],
    queryFn: () => clinicApi.patient(id!),
    enabled: isEdit,
  });

  const doctors = useMemo(() => {
    const root = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
    return Array.isArray(root) ? (root as Array<{ id: string | number; name?: string }>) : [];
  }, [doctorsQuery.data]);

  useEffect(() => {
    if (!detailsQuery.data || !isEdit) return;
    const root = (detailsQuery.data as { data?: unknown })?.data ?? detailsQuery.data;
    const p = root as {
      doctor_id?: number;
      doctor_ids?: Array<number | string>;
      name?: string;
      address?: string;
      email?: string;
      phone?: string;
      whatsapp_number?: string;
      age?: string;
      gender?: "male" | "female";
      blood_group?: FormState["blood_group"];
      height?: string;
      weight?: string;
    };
    setForm({
      doctor_ids: Array.isArray(p.doctor_ids)
        ? p.doctor_ids.map((doctorId) => String(doctorId))
        : p.doctor_id
          ? [String(p.doctor_id)]
          : [],
      name: p.name ?? "",
      address: p.address ?? "",
      email: p.email ?? "",
      password: "",
      phone: p.phone ?? "",
      whatsapp_number: p.whatsapp_number ?? "",
      age: p.age ?? "",
      gender: p.gender ?? "male",
      blood_group: p.blood_group ?? "",
      height: p.height ?? "",
      weight: p.weight ?? "",
    });
  }, [detailsQuery.data, isEdit]);

  const mutation = useMutation({
    mutationFn: async () => {
      const payload = {
        doctor_ids: form.doctor_ids.map((doctorId) => Number(doctorId)),
        name: form.name,
        address: form.address,
        email: form.email || undefined,
        password: form.password || undefined,
        phone: form.phone,
        whatsapp_number: form.whatsapp_number || undefined,
        age: form.age || undefined,
        gender: form.gender,
        blood_group: form.blood_group || undefined,
        height: form.height || undefined,
        weight: form.weight || undefined,
      };
      if (isEdit && id) return clinicApi.updatePatient(id, payload);
      return clinicApi.createPatient(payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["clinic", "patients"] });
      await queryClient.invalidateQueries({ queryKey: ["clinic", "patients", "all"] });
      toast({ title: isEdit ? t("clinic.patients.patient_updated") : t("clinic.patients.patient_created") });
      navigate("/clinic-dashboard/patients");
    },
    onError: (e) => {
      toast({
        title: t("clinic.patients.failed_to_save_patient"),
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const onSubmit = () => {
    if (form.doctor_ids.length === 0 || !form.name || !form.address || !form.phone || !form.gender) {
      toast({
        title: t("clinic.patients.missing_required_fields"),
        description: t("clinic.patients.at_least_one_doctor_name_address_phone_and_gender_are_required"),
        variant: "destructive",
      });
      return;
    }
    mutation.mutate();
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{isEdit ? t("clinic.patients.edit") : t("clinic.patients.create")}</h2>
        </div>
        <Button asChild variant="outline" className="gap-2">
          <Link to="/clinic-dashboard/patients">
            <ArrowLeft className="h-4 w-4" />
            {t("clinic.patients.back")}
          </Link>
        </Button>
      </div>

      <div className="rounded-xl border bg-card p-4 space-y-4">
        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>{t("clinic.patients.assigned_doctors")} *</Label>
            <div className="max-h-44 overflow-y-auto rounded-md border p-3 space-y-2">
              {doctors.length === 0 ? (
                <p className="text-xs text-muted-foreground">{t("clinic.patients.no_doctors_available")}</p>
              ) : (
                doctors.map((d) => {
                  const doctorId = String(d.id);
                  const checked = form.doctor_ids.includes(doctorId);
                  return (
                    <label key={doctorId} className="flex items-center gap-2 text-sm cursor-pointer">
                      <Checkbox
                        checked={checked}
                        onCheckedChange={(value) => {
                          const isChecked = Boolean(value);
                          setForm((prev) => ({
                            ...prev,
                            doctor_ids: isChecked
                              ? Array.from(new Set([...prev.doctor_ids, doctorId]))
                              : prev.doctor_ids.filter((id) => id !== doctorId),
                          }));
                        }}
                      />
                      <span>{d.name ?? `Doctor ${d.id}`}</span>
                    </label>
                  );
                })
              )}
            </div>
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.patient_name")} *</Label>
            <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>{t("clinic.patients.address")} *</Label>
            <Input value={form.address} onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.email")}</Label>
            <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
          </div>
        </div>

        <div className="grid sm:grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label>{t("clinic.patients.password")} {isEdit ? `(${t("clinic.patients.optional")})` : ""}</Label>
            <Input
              type="password"
              value={form.password}
              onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
              placeholder={isEdit ? t("clinic.patients.leave_empty_to_keep_current_password") : ""}
            />
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.phone")} *</Label>
            <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.whatsapp_number")}</Label>
            <Input value={form.whatsapp_number} onChange={(e) => setForm((f) => ({ ...f, whatsapp_number: e.target.value }))} />
          </div>
        </div>

        <div className="grid sm:grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label>{t("clinic.patients.age")}</Label>
            <Input value={form.age} onChange={(e) => setForm((f) => ({ ...f, age: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.gender")} *</Label>
            <Select value={form.gender} onValueChange={(v) => setForm((f) => ({ ...f, gender: v as FormState["gender"] }))}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="male">{t("clinic.patients.male")}</SelectItem>
                <SelectItem value="female">{t("clinic.patients.female")}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.blood_type")}</Label>
            <Select value={form.blood_group || "none"} onValueChange={(v) => setForm((f) => ({ ...f, blood_group: v === "none" ? "" : (v as FormState["blood_group"]) }))}>
              <SelectTrigger><SelectValue placeholder={t("clinic.patients.select_blood_type")} /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">{t("clinic.patients.not_set")}</SelectItem>
                <SelectItem value="A+">A+</SelectItem>
                <SelectItem value="A-">A-</SelectItem>
                <SelectItem value="B+">B+</SelectItem>
                <SelectItem value="B-">B-</SelectItem>
                <SelectItem value="O+">O+</SelectItem>
                <SelectItem value="O-">O-</SelectItem>
                <SelectItem value="AB+">AB+</SelectItem>
                <SelectItem value="AB-">AB-</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>{t("clinic.patients.height")}</Label>
            <Input value={form.height} onChange={(e) => setForm((f) => ({ ...f, height: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>{t("clinic.patients.weight")}</Label>
            <Input value={form.weight} onChange={(e) => setForm((f) => ({ ...f, weight: e.target.value }))} />
          </div>
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <Button variant="outline" onClick={() => navigate("/clinic-dashboard/patients")} disabled={mutation.isPending}>
            {t("clinic.patients.cancel")}
          </Button>
          <Button onClick={onSubmit} disabled={mutation.isPending} className="gradient-primary text-primary-foreground border-0">
            {mutation.isPending ? t("clinic.patients.saving") : isEdit ? t("clinic.patients.save_changes") : t("clinic.patients.create_patient")}
          </Button>
        </div>
      </div>
    </div>
  );
}
