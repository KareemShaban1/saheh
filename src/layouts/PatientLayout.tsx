import { Outlet, Link, useLocation, useNavigate } from "react-router-dom";
import {
  CalendarDays,
  User,
  FileText,
  FlaskConical,
  ScanLine,
  Heart,
  Glasses,
  Star,
  MessageCircle,
  Stethoscope,
  Menu,
  LogOut,
  Home,
} from "lucide-react";
import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";
import { useLanguage } from "@/contexts/LanguageContext";
import { useAuth } from "@/contexts/AuthContext";
import { InstallPwaButton } from "@/components/PwaInstallPrompt";
import { PatientNotificationsBell } from "@/components/PatientNotificationsBell";
import { syncWebPushSubscription } from "@/lib/webPush";

const sidebarItems = [
  { key: "nav.home", to: "/patient/home", icon: Home },
  { key: "patient.menu.appointments", to: "/patient/appointments", icon: CalendarDays },
  { key: "patient.menu.profile", to: "/patient/profile", icon: User },
  { key: "patient.menu.prescriptions", to: "/patient/prescriptions", icon: FileText },
  { key: "patient.menu.analyses", to: "/patient/analyses", icon: FlaskConical },
  { key: "patient.menu.rays", to: "/patient/rays", icon: ScanLine },
  { key: "patient.menu.chronic", to: "/patient/chronic-diseases", icon: Heart },
  { key: "patient.menu.glasses", to: "/patient/glasses", icon: Glasses },
  { key: "patient.menu.reviews", to: "/patient/reviews", icon: Star },
  { key: "patient.menu.questionnaires", to: "/patient/questionnaires", icon: FileText },
  { key: "patient.menu.reels", to: "/patient/reels", icon: ScanLine },
  { key: "patient.menu.chat", to: "/patient/chat", icon: MessageCircle },
];

const mobileBottomItems = [
  { key: "nav.home", to: "/patient/home", icon: Home, label: "Home" },
  { key: "patient.menu.appointments", to: "/patient/appointments", icon: CalendarDays, label: "Reservations" },
  { key: "patient.menu.analyses", to: "/patient/analyses", icon: FlaskConical, label: "Analyses" },
  { key: "patient.menu.rays", to: "/patient/rays", icon: ScanLine, label: "Rays" },
  { key: "patient.menu.reels", to: "/patient/reels", icon: ScanLine, label: "Reels" },
  { key: "patient.menu.profile", to: "/patient/profile", icon: User, label: "Profile" },
];

export default function PatientLayout() {
  const [collapsed, setCollapsed] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const { dir, t, lang, toggleLanguage } = useLanguage();
  const { logout, token, isAuthenticated } = useAuth();
  const isReelsRoute = location.pathname === "/patient/reels";

  useEffect(() => {
    if (!isAuthenticated || !token) return;
    void syncWebPushSubscription("patient", token).catch(() => {
      /* optional */
    });
  }, [isAuthenticated, token]);

  const handleLogout = () => {
    logout();
    navigate("/");
  };

  return (
    <div className="min-h-screen flex w-full" dir={dir}>
      {!isReelsRoute && (
        <aside
          className={cn(
            "hidden md:flex flex-col bg-card border-r transition-all duration-300 shrink-0",
            collapsed ? "w-16" : "w-64"
          )}
        >
          <div className="h-16 flex items-center gap-2 px-4 border-b">
            <div className="h-8 w-8 rounded-lg gradient-primary flex items-center justify-center shrink-0">
              <Stethoscope className="h-4 w-4 text-primary-foreground" />
            </div>
            {!collapsed && <span className="font-bold text-lg">{t("brand.name")}</span>}
          </div>
          <nav className="flex-1 p-3 space-y-1">
            {sidebarItems.map((item) => {
              const active = location.pathname === item.to;
              return (
                <Link
                  key={item.to}
                  to={item.to}
                  className={cn(
                    "flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors",
                    active
                      ? "bg-sidebar-accent text-primary"
                      : "text-muted-foreground hover:text-foreground hover:bg-muted",
                  )}
                  title={collapsed ? t(item.key) : undefined}
                >
                  <item.icon className="h-4 w-4 shrink-0" />
                  {!collapsed && <span>{t(item.key)}</span>}
                </Link>
              );
            })}
          </nav>
          <div className="p-3 border-t space-y-2">
            {!collapsed ? (
              <InstallPwaButton className="w-full justify-center" />
            ) : null}
            <Link to="/" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-muted-foreground hover:text-foreground hover:bg-muted">
              <Home className="h-4 w-4 shrink-0" />
              {!collapsed && <span>{t("nav.backToHome")}</span>}
            </Link>
          </div>
        </aside>
      )}

      <div className="flex-1 flex flex-col min-w-0">
        {!isReelsRoute && (
          <header className="h-16 flex items-center gap-4 px-4 md:px-6 border-b bg-card">
          <button onClick={() => setCollapsed(!collapsed)} className="p-2 rounded-lg hover:bg-muted hidden md:block" aria-label="Toggle sidebar">
            <Menu className="h-5 w-5" />
          </button>
          <h1 className="text-lg font-semibold">{t("patient.title")}</h1>
          <div className="ml-auto flex items-center gap-2">
            {token ? <PatientNotificationsBell token={token} /> : null}
            <button
              className="px-2 py-1 rounded-lg text-xs text-muted-foreground hover:bg-muted"
              onClick={toggleLanguage}
            >
              {lang === "en" ? t("lang.ar") : t("lang.en")}
            </button>
            <button
              type="button"
              onClick={handleLogout}
              className="p-2 rounded-lg hover:bg-muted text-muted-foreground"
              aria-label={t("auth.logout")}
            >
              <LogOut className="h-4 w-4" />
            </button>
          </div>
          </header>
        )}

        {!isReelsRoute && (
          <div className="md:hidden border-b bg-card px-3 py-2">
            <div className="flex gap-2 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
              {sidebarItems.map((item) => {
                const active = location.pathname === item.to;
                return (
                  <Link
                    key={item.to}
                    to={item.to}
                    className={cn(
                      "shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium",
                      active ? "border-primary/40 bg-sidebar-accent text-primary" : "text-muted-foreground hover:bg-muted",
                    )}
                  >
                    {t(item.key)}
                  </Link>
                );
              })}
            </div>
          </div>
        )}

        {/* Mobile bottom nav */}
        <nav className="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-card border-t flex justify-around py-2">
          {mobileBottomItems.map((item) => {
            const active = location.pathname === item.to;
            return (
              <Link key={item.to} to={item.to} className={cn("flex flex-col items-center gap-0.5 text-xs", active ? "text-primary" : "text-muted-foreground")}>
                <item.icon className="h-5 w-5" />
                <span className="truncate max-w-[70px]">{t(item.key) || item.label}</span>
              </Link>
            );
          })}
        </nav>

        <main
          className={cn(
            "flex-1",
            location.pathname === "/patient/reels"
              ? "p-0 overflow-hidden"
              : "p-4 md:p-6 pb-24 md:pb-6 overflow-auto",
          )}
        >
          <Outlet />
        </main>
      </div>
    </div>
  );
}
