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
      toast({ title: isEdit ? "Patient updated" : "Patient created" });
      navigate("/clinic-dashboard/patients");
    },
    onError: (e) => {
      toast({
        title: "Failed to save patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const onSubmit = () => {
    if (form.doctor_ids.length === 0 || !form.name || !form.address || !form.phone || !form.gender) {
      toast({
        title: "Missing required fields",
        description: "At least one doctor, name, address, phone and gender are required.",
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
          <h2 className="text-2xl font-bold">{isEdit ? "Edit Patient" : "Create Patient"}</h2>
          <p className="text-muted-foreground text-sm mt-1">Manage patient profile and related doctor</p>
        </div>
        <Button asChild variant="outline" className="gap-2">
          <Link to="/clinic-dashboard/patients">
            <ArrowLeft className="h-4 w-4" />
            Back
          </Link>
        </Button>
      </div>

      <div className="rounded-xl border bg-card p-4 space-y-4">
        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>Assigned Doctors *</Label>
            <div className="max-h-44 overflow-y-auto rounded-md border p-3 space-y-2">
              {doctors.length === 0 ? (
                <p className="text-xs text-muted-foreground">No doctors available.</p>
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
            <Label>Patient Name *</Label>
            <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>Address *</Label>
            <Input value={form.address} onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Email</Label>
            <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
          </div>
        </div>

        <div className="grid sm:grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label>Password {isEdit ? "(optional)" : ""}</Label>
            <Input
              type="password"
              value={form.password}
              onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
              placeholder={isEdit ? "Leave empty to keep current password" : ""}
            />
          </div>
          <div className="space-y-2">
            <Label>Phone *</Label>
            <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Whatsapp Number</Label>
            <Input value={form.whatsapp_number} onChange={(e) => setForm((f) => ({ ...f, whatsapp_number: e.target.value }))} />
          </div>
        </div>

        <div className="grid sm:grid-cols-3 gap-4">
          <div className="space-y-2">
            <Label>Age</Label>
            <Input value={form.age} onChange={(e) => setForm((f) => ({ ...f, age: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Gender *</Label>
            <Select value={form.gender} onValueChange={(v) => setForm((f) => ({ ...f, gender: v as FormState["gender"] }))}>
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="male">male</SelectItem>
                <SelectItem value="female">female</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Blood Type</Label>
            <Select value={form.blood_group || "none"} onValueChange={(v) => setForm((f) => ({ ...f, blood_group: v === "none" ? "" : (v as FormState["blood_group"]) }))}>
              <SelectTrigger><SelectValue placeholder="Select blood type" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Not set</SelectItem>
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
            <Label>Height</Label>
            <Input value={form.height} onChange={(e) => setForm((f) => ({ ...f, height: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Weight</Label>
            <Input value={form.weight} onChange={(e) => setForm((f) => ({ ...f, weight: e.target.value }))} />
          </div>
        </div>

        <div className="flex justify-end gap-2 pt-2">
          <Button variant="outline" onClick={() => navigate("/clinic-dashboard/patients")} disabled={mutation.isPending}>
            Cancel
          </Button>
          <Button onClick={onSubmit} disabled={mutation.isPending} className="gradient-primary text-primary-foreground border-0">
            {mutation.isPending ? "Saving..." : isEdit ? "Save Changes" : "Create Patient"}
          </Button>
        </div>
      </div>
    </div>
  );
}
