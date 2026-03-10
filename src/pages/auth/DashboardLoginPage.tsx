import { Link } from "react-router-dom";
import { Stethoscope, Building2, Shield, FlaskConical, ScanLine } from "lucide-react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useLanguage } from "@/contexts/LanguageContext";

const dashboards = [
  { key: "super-admin", loginPath: "/super-admin/login", icon: Shield, title: "Super Admin", registerPath: null },
  { key: "clinic", loginPath: "/clinic/login", registerPath: "/clinic/register", icon: Building2, titleKey: "public.footer.clinic" },
  { key: "lab", loginPath: "/lab/login", registerPath: "/lab/register", icon: FlaskConical, titleKey: "nav.labs" },
  { key: "radiology", loginPath: "/radiology/login", registerPath: "/radiology/register", icon: ScanLine, titleKey: "nav.radiology" },
];

export default function DashboardLoginPage() {
  const { t } = useLanguage();

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4">
      <Card className="w-full max-w-lg">
        <CardHeader className="text-center">
          <Link to="/" className="inline-flex items-center gap-2 justify-center font-bold text-xl mb-2">
            <div className="h-10 w-10 rounded-lg gradient-primary flex items-center justify-center">
              <Stethoscope className="h-5 w-5 text-primary-foreground" />
            </div>
            {t("brand.name")}
          </Link>
          <CardTitle>{t("auth.dashboardLogin")}</CardTitle>
          <CardDescription>{t("auth.dashboardLoginDescription")}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-3">
          {dashboards.map((d) => (
            <div key={d.key} className="flex gap-2">
              <Link to={d.loginPath} className="flex-1">
                <Button variant="outline" className="w-full justify-start gap-3 h-12" asChild>
                  <span>
                    <d.icon className="h-5 w-5" />
                    {d.title ?? t(d.titleKey!)} — {t("auth.signIn")}
                  </span>
                </Button>
              </Link>
              {d.registerPath ? (
                <Link to={d.registerPath}>
                  <Button variant="secondary" className="h-12">{t("auth.register")}</Button>
                </Link>
              ) : null}
            </div>
          ))}
          <p className="text-xs text-muted-foreground text-center pt-2">
            {t("auth.dashboardLoginNote")}
          </p>
          <div className="pt-4 flex justify-center">
            <Link to="/">
              <Button variant="ghost" size="sm">{t("nav.backToHome")}</Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
