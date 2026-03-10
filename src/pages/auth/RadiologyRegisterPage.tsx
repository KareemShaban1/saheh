import { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Stethoscope, ScanLine } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { useLanguage } from "@/contexts/LanguageContext";
import { organizationAuthApi } from "@/lib/api";
import { BASE_URL } from "@/lib/api";

export default function RadiologyRegisterPage() {
  const { t } = useLanguage();
  const navigate = useNavigate();
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const [governorates, setGovernorates] = useState<{ id: number; name: string }[]>([]);
  const [form, setForm] = useState({
    radiology_center_name: "",
    start_date: new Date().toISOString().slice(0, 10),
    governorate_id: "",
    city_id: "",
    area_id: "",
    address: "",
    phone: "",
    radiology_center_email: "",
    user_name: "",
    user_email: "",
    password: "",
    password_confirmation: "",
  });

  useEffect(() => {
    fetch(`${BASE_URL}/public/governorates`).then((r) => r.json()).catch(() => ({ data: [] })).then((g) => {
      const gd = (g as { data?: unknown[] }).data ?? g as unknown[];
      setGovernorates(Array.isArray(gd) ? gd as { id: number; name: string }[] : []);
    });
  }, []);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    if (form.password !== form.password_confirmation) {
      setError(t("auth.passwordMismatch"));
      return;
    }
    setLoading(true);
    try {
      const res = await organizationAuthApi.radiologyCenterRegister({
        ...form,
        governorate_id: form.governorate_id ? Number(form.governorate_id) : undefined,
        city_id: form.city_id ? Number(form.city_id) : undefined,
        area_id: form.area_id ? Number(form.area_id) : undefined,
      });
      if ((res as { success?: boolean }).success) {
        navigate("/dashboard-login", { replace: true });
        return;
      }
      setError((res as { message?: string }).message || "Submitted for review.");
    } catch (err: unknown) {
      const r = err as { message?: string; errors?: Record<string, string[]> };
      const first = r?.errors ? Object.values(r.errors).flat()[0] : null;
      setError(first || r?.message || "Registration failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4 py-10">
      <Card className="w-full max-w-lg">
        <CardHeader className="text-center">
          <Link to="/" className="inline-flex items-center gap-2 justify-center font-bold text-xl mb-2">
            <div className="h-10 w-10 rounded-lg gradient-primary flex items-center justify-center">
              <Stethoscope className="h-5 w-5 text-primary-foreground" />
            </div>
            {t("brand.name")}
          </Link>
          <ScanLine className="h-10 w-10 text-primary mx-auto" />
          <CardTitle>{t("auth.radiologyRegister")}</CardTitle>
          <CardDescription>{t("auth.radiologyRegisterDesc")}</CardDescription>
        </CardHeader>
        <form onSubmit={handleSubmit}>
          <CardContent className="space-y-4">
            {error && (
              <div className="rounded-lg bg-destructive/10 text-destructive text-sm p-3">{error}</div>
            )}
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>{t("auth.radiologyName")}</Label>
                <Input name="radiology_center_name" value={form.radiology_center_name} onChange={handleChange} required />
              </div>
              <div className="space-y-2">
                <Label>{t("auth.startDate")}</Label>
                <Input name="start_date" type="date" value={form.start_date} onChange={handleChange} required />
              </div>
            </div>
            <div className="space-y-2">
              <Label>{t("auth.address")}</Label>
              <Input name="address" value={form.address} onChange={handleChange} required />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>{t("auth.phone")}</Label>
                <Input name="phone" value={form.phone} onChange={handleChange} required />
              </div>
              <div className="space-y-2">
                <Label>{t("auth.radiologyEmail")}</Label>
                <Input name="radiology_center_email" type="email" value={form.radiology_center_email} onChange={handleChange} required />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>{t("auth.name")}</Label>
                <Input name="user_name" value={form.user_name} onChange={handleChange} required />
              </div>
              <div className="space-y-2">
                <Label>{t("auth.email")}</Label>
                <Input name="user_email" type="email" value={form.user_email} onChange={handleChange} required />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label>{t("auth.password")}</Label>
                <Input name="password" type="password" value={form.password} onChange={handleChange} required minLength={6} />
              </div>
              <div className="space-y-2">
                <Label>{t("auth.confirmPassword")}</Label>
                <Input name="password_confirmation" type="password" value={form.password_confirmation} onChange={handleChange} required />
              </div>
            </div>
          </CardContent>
          <CardFooter className="flex flex-col gap-3">
            <Button type="submit" className="w-full gradient-primary border-0" disabled={loading}>
              {loading ? t("auth.creatingAccount") : t("auth.register")}
            </Button>
            <p className="text-sm text-muted-foreground">
              {t("auth.hasAccount")}{" "}
              <Link to="/radiology/login" className="text-primary font-medium hover:underline">{t("auth.login")}</Link>
            </p>
          </CardFooter>
        </form>
      </Card>
    </div>
  );
}
