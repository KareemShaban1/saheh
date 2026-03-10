import { useState, useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { User, Edit } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useAuth } from "@/contexts/AuthContext";
import { useLanguage } from "@/contexts/LanguageContext";
import { patientApi } from "@/lib/api";

export default function PatientProfile() {
  const { t } = useLanguage();
  const { token, patient, setPatient } = useAuth();
  const [editing, setEditing] = useState(false);
  const [form, setForm] = useState({
    name: patient?.name ?? "",
    email: patient?.email ?? "",
    phone: patient?.phone ?? "",
    address: patient?.address ?? "",
  });
  useEffect(() => {
    if (patient) setForm({ name: patient.name ?? "", email: patient.email ?? "", phone: patient.phone ?? "", address: patient.address ?? "" });
  }, [patient]);
  const queryClient = useQueryClient();
  const updateMutation = useMutation({
    mutationFn: (data: typeof form) => patientApi.updateProfile(token!, data),
    onSuccess: (_, variables) => {
      setPatient?.({ ...patient!, ...variables });
      setEditing(false);
      queryClient.invalidateQueries({ queryKey: ["patient"] });
    },
  });

  const p = patient ?? {};
  const display = editing ? form : { name: p.name, email: p.email, phone: p.phone, address: p.address };

  return (
    <div className="max-w-3xl">
      <h2 className="text-2xl font-bold mb-6">My Profile</h2>

      <div className="bg-card rounded-xl border p-4 sm:p-6 space-y-6">
        <div className="flex flex-wrap items-center gap-4">
          <div className="h-16 w-16 rounded-full bg-sidebar-accent flex items-center justify-center">
            <User className="h-8 w-8 text-primary" />
          </div>
          <div>
            <p className="font-semibold text-lg">{String(p.name ?? "—")}</p>
            <p className="text-sm text-muted-foreground">Patient ID: #{String(p.id ?? "—")}</p>
          </div>
          {!editing ? (
            <Button variant="outline" size="sm" className="sm:ml-auto gap-2 w-full sm:w-auto" onClick={() => setEditing(true)}>
              <Edit className="h-3.5 w-3.5" /> {t("auth.edit")}
            </Button>
          ) : (
            <div className="sm:ml-auto flex gap-2 w-full sm:w-auto">
              <Button variant="ghost" size="sm" className="flex-1 sm:flex-none" onClick={() => setEditing(false)}>Cancel</Button>
              <Button
                size="sm"
                className="gradient-primary border-0 flex-1 sm:flex-none"
                disabled={updateMutation.isPending}
                onClick={() => updateMutation.mutate(form)}
              >
                {updateMutation.isPending ? "Saving…" : "Save"}
              </Button>
            </div>
          )}
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>{t("auth.name")}</Label>
            <Input
              value={display.name}
              readOnly={!editing}
              onChange={editing ? (e) => setForm((f) => ({ ...f, name: e.target.value })) : undefined}
            />
          </div>
          <div className="space-y-2">
            <Label>{t("auth.email")}</Label>
            <Input
              type="email"
              value={display.email}
              readOnly={!editing}
              onChange={editing ? (e) => setForm((f) => ({ ...f, email: e.target.value })) : undefined}
            />
          </div>
          <div className="space-y-2">
            <Label>{t("auth.phone")}</Label>
            <Input
              value={display.phone}
              readOnly={!editing}
              onChange={editing ? (e) => setForm((f) => ({ ...f, phone: e.target.value })) : undefined}
            />
          </div>
          <div className="space-y-2">
            <Label>{t("auth.address")}</Label>
            <Input
              value={display.address}
              readOnly={!editing}
              onChange={editing ? (e) => setForm((f) => ({ ...f, address: e.target.value })) : undefined}
            />
          </div>
        </div>
      </div>

      <div className="bg-card rounded-xl border p-4 sm:p-6 mt-6">
        <h3 className="font-semibold mb-4">Change Password</h3>
        <div className="space-y-4 max-w-md">
          <div className="space-y-2">
            <Label>Current Password</Label>
            <Input type="password" placeholder="••••••••" id="current_password" />
          </div>
          <div className="space-y-2">
            <Label>New Password</Label>
            <Input type="password" placeholder="••••••••" id="new_password" />
          </div>
          <Button className="gradient-primary text-primary-foreground border-0">Update Password</Button>
        </div>
      </div>
    </div>
  );
}
