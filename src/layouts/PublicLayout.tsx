import { Outlet, Link, useLocation } from "react-router-dom";
import { motion } from "framer-motion";
import { Stethoscope, Menu, X } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { useLanguage } from "@/contexts/LanguageContext";
import { InstallPwaButton } from "@/components/PwaInstallPrompt";

const navLinks = [
  { key: "nav.home", to: "/" },
  { key: "nav.clinics", to: "/clinics" },
  { key: "nav.labs", to: "/labs" },
  { key: "nav.radiology", to: "/radiology-centers" },
	{ key: "auth.dashboardLogin", to: "/dashboard-login" },
];

export default function PublicLayout() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const location = useLocation();
  const { lang, dir, t, toggleLanguage } = useLanguage();

  return (
    <div className="min-h-screen flex flex-col" dir={dir}>
      <header className="sticky top-0 z-50 bg-card/80 backdrop-blur-lg border-b">
        <div className="container flex items-center justify-between h-16">
          <Link to="/" className="flex items-center gap-2 font-bold text-xl">
            <div className="h-9 w-9 rounded-lg gradient-primary flex items-center justify-center">
              <Stethoscope className="h-5 w-5 text-primary-foreground" />
            </div>
            <span className="text-foreground">{t("brand.name")}</span>
          </Link>

          <nav className="hidden md:flex items-center gap-1">
            {navLinks.map((link) => (
              <Link
                key={link.to}
                to={link.to}
                className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                  location.pathname === link.to
                    ? "bg-sidebar-accent text-primary"
                    : "text-muted-foreground hover:text-foreground hover:bg-muted"
                }`}
              >
                {t(link.key)}
              </Link>
            ))}
          </nav>

          <div className="hidden md:flex items-center gap-3">
            <InstallPwaButton />
            <Button
              variant="outline"
              size="sm"
              onClick={toggleLanguage}
              className="text-xs"
            >
              {lang === "en" ? t("lang.ar") : t("lang.en")}
            </Button>
            <Link to="/login">
              <Button variant="outline" size="sm">{t("auth.signIn")}</Button>
            </Link>
            <Link to="/register">
              <Button size="sm">{t("auth.getStarted")}</Button>
            </Link>
          </div>

          <button
            className="md:hidden p-2 rounded-lg hover:bg-muted"
            onClick={() => setMobileOpen(!mobileOpen)}
            aria-label="Toggle menu"
          >
            {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
        </div>

        {mobileOpen && (
          <motion.div
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            className="md:hidden border-t bg-card p-4 space-y-2"
          >
            {navLinks.map((link) => (
              <Link
                key={link.to}
                to={link.to}
                onClick={() => setMobileOpen(false)}
                className="block px-4 py-2 rounded-lg text-sm font-medium hover:bg-muted"
              >
                {t(link.key)}
              </Link>
            ))}
            <div className="pt-2">
              <InstallPwaButton className="w-full mb-2" />
            </div>
            <div className="pt-0 flex gap-2">
              <Button
                variant="outline"
                size="sm"
                className="flex-1"
                onClick={toggleLanguage}
              >
                {lang === "en" ? t("lang.ar") : t("lang.en")}
              </Button>
              <Link to="/login" className="flex-1">
                <Button variant="outline" size="sm" className="w-full">{t("auth.signIn")}</Button>
              </Link>
              <Link to="/register" className="flex-1">
                <Button size="sm" className="w-full">{t("auth.getStarted")}</Button>
              </Link>
            </div>
          </motion.div>
        )}
      </header>

      <main className="flex-1">
        <Outlet />
      </main>

      <footer className="border-t bg-card py-12">
        <div className="container">
          <div className="flex flex-col md:flex-row justify-between gap-8">
            <div>
              <Link to="/" className="flex items-center gap-2 font-bold text-lg">
                <div className="h-8 w-8 rounded-lg gradient-primary flex items-center justify-center">
                  <Stethoscope className="h-4 w-4 text-primary-foreground" />
                </div>
                {t("brand.name")}
              </Link>
              <p className="mt-2 text-sm text-muted-foreground max-w-xs">
                {t("public.footer.description")}
              </p>
            </div>
            <div className="flex gap-12 text-sm">
              <div className="space-y-2">
                <p className="font-semibold">{t("public.footer.platform")}</p>
                <Link to="/clinics" className="block text-muted-foreground hover:text-foreground">
                  {t("nav.clinics")}
                </Link>
                <Link to="/labs" className="block text-muted-foreground hover:text-foreground">
                  {t("nav.labs")}
                </Link>
                <Link to="/radiology-centers" className="block text-muted-foreground hover:text-foreground">
                  {t("nav.radiology")}
                </Link>
		
              </div>
              <div className="space-y-2">
                <p className="font-semibold">{t("public.footer.dashboards")}</p>
                <Link to="/dashboard-login" className="block text-muted-foreground hover:text-foreground">
                  {t("auth.dashboardLogin")}
                </Link>
                <Link to="/clinic-dashboard" className="block text-muted-foreground hover:text-foreground">
                  {t("public.footer.clinic")}
                </Link>
                <Link to="/super-admin/login" className="block text-muted-foreground hover:text-foreground">
                  {t("public.footer.admin")}
                </Link>
              </div>
            </div>
          </div>
          <div className="mt-8 pt-6 border-t text-center text-xs text-muted-foreground">
            © 2026 {t("brand.name")}. {t("public.footer.copyright")}
          </div>
        </div>
      </footer>
    </div>
  );
}
