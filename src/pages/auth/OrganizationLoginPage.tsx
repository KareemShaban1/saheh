import { useState } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { Stethoscope, Building2, FlaskConical, ScanLine } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { useLanguage } from "@/contexts/LanguageContext";
import { organizationAuthApi, setOrganizationToken, setOrganizationUser } from "@/lib/api";

const types = {
  clinic: { titleKey: "auth.clinicLogin", descKey: "auth.clinicLoginDesc", icon: Building2, dashboardPath: "/clinic-dashboard" },
  lab: { titleKey: "auth.labLogin", descKey: "auth.labLoginDesc", icon: FlaskConical, dashboardPath: "/lab-dashboard" },
  radiology: { titleKey: "auth.radiologyLogin", descKey: "auth.radiologyLoginDesc", icon: ScanLine, dashboardPath: "/radiology-dashboard" },
} as const;

type OrgType = keyof typeof types;

export default function OrganizationLoginPage() {
  const { t } = useLanguage();
  const navigate = useNavigate();
  const pathname = useLocation().pathname;
  const orgType: OrgType = pathname.includes("/lab/") ? "lab" : pathname.includes("/radiology/") ? "radiology" : "clinic";
  const config = types[orgType];
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const res =
        orgType === "clinic"
          ? await organizationAuthApi.clinicLogin(email, password)
          : orgType === "lab"
            ? await organizationAuthApi.medicalLaboratoryLogin(email, password)
            : await organizationAuthApi.radiologyCenterLogin(email, password);
      if (res.token) {
        setOrganizationToken(res.token);
        if (res.user) setOrganizationUser(res.user);
        navigate(config.dashboardPath, { replace: true });
      } else {
        setError((res as { message?: string }).message || "Login failed");
      }
    } catch (err: unknown) {
      const r = err as { message?: string; errors?: Record<string, string[]> };
      const first = r?.errors ? Object.values(r.errors).flat()[0] : null;
      setError(first || r?.message || "Invalid email or password");
    } finally {
      setLoading(false);
    }
  };

  const Icon = config.icon;
  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <Link to="/" className="inline-flex items-center gap-2 justify-center font-bold text-xl mb-2">
            <div className="h-10 w-10 rounded-lg gradient-primary flex items-center justify-center">
              <Stethoscope className="h-5 w-5 text-primary-foreground" />
            </div>
            {t("brand.name")}
          </Link>
          <div className="flex justify-center">
            <Icon className="h-10 w-10 text-primary" />
          </div>
          <CardTitle>{t(config.titleKey)}</CardTitle>
          <CardDescription>{t(config.descKey)}</CardDescription>
        </CardHeader>
        <form onSubmit={handleSubmit}>
          <CardContent className="space-y-4">
            {error && (
              <div className="rounded-lg bg-destructive/10 text-destructive text-sm p-3">{error}</div>
            )}
            <div className="space-y-2">
              <Label htmlFor="email">{t("auth.email")}</Label>
              <Input
                id="email"
                type="email"
                placeholder="you@example.com"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                autoComplete="email"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="password">{t("auth.password")}</Label>
              <Input
                id="password"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoComplete="current-password"
              />
            </div>
          </CardContent>
          <CardFooter className="flex flex-col gap-3">
            <Button type="submit" className="w-full gradient-primary border-0" disabled={loading}>
              {loading ? t("auth.signingIn") : t("auth.signIn")}
            </Button>
            <p className="text-sm text-muted-foreground">
              {t("auth.noAccount")}{" "}
              <Link to={`/${orgType}/register`} className="text-primary font-medium hover:underline">
                {t("auth.register")}
              </Link>
            </p>
            <Link to="/dashboard-login">
              <Button type="button" variant="ghost" size="sm">{t("auth.backToDashboardLogin")}</Button>
            </Link>
          </CardFooter>
        </form>
      </Card>
    </div>
  );
}
