import { Outlet, Link, useLocation, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import {
  LayoutDashboard,
  CalendarDays,
  ChevronDown,
  Users,
  UserCog,
  Shield,
  Clock,
  Hash,
  Stethoscope,
  Menu,
  Trash2,
  Home,
  MessageCircle,
  Star,
  Bell,
  Puzzle,
  Boxes,
  FileText,
  LogOut,
  User,
  Settings,
	List,
} from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { useLanguage } from "@/contexts/LanguageContext";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useToast } from "@/hooks/use-toast";
import {
  getOrganizationToken,
  getOrganizationUser,
  setOrganizationUser,
  clearOrganizationUser,
  clearOrganizationToken,
  organizationAuthApi,
  getSuperAdminToken,
  getSuperAdminUser,
  setSuperAdminUser,
  clearSuperAdminUser,
  clearSuperAdminToken,
  superAdminAuthApi,
  clinicApi,
  labApi,
  radiologyApi,
} from "@/lib/api";
import { hasOrganizationAccess } from "@/lib/organizationAccess";

interface DashboardItem {
  label: string;
  to: string;
  icon: React.ComponentType<{ className?: string }>;
  labelKey?: string;
  requiredPermissions?: string[];
  permissionPrefixes?: string[];
  children?: DashboardItem[];
}

interface DashboardLayoutProps {
  title: string;
  titleKey?: string;
  basePath: string;
  items: DashboardItem[];
}

export function DashboardLayout({ title, titleKey, basePath, items }: DashboardLayoutProps) {
  const [collapsed, setCollapsed] = useState(false);
  const [submenuOpen, setSubmenuOpen] = useState<Record<string, boolean>>({});
  const [viewOpen, setViewOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [savingProfile, setSavingProfile] = useState(false);
  const [profileForm, setProfileForm] = useState({
    name: "",
    email: "",
    phone: "",
    job_title: "",
    password: "",
  });
  const location = useLocation();
  const navigate = useNavigate();
  const { dir, t, lang, toggleLanguage } = useLanguage();
  const { toast } = useToast();
  const displayTitle = titleKey ? t(titleKey) : title;
  const orgToken = getOrganizationToken();
  const superAdminToken = getSuperAdminToken();
  const isSuperAdminArea = basePath.startsWith("/super-admin") || basePath === "/admin";
  const canLogout = isSuperAdminArea ? !!superAdminToken : !!orgToken;
  const [currentUser, setCurrentUser] = useState<Record<string, unknown> | null>(() => {
    const user = isSuperAdminArea ? getSuperAdminUser() : getOrganizationUser();
    return (user as Record<string, unknown> | null) ?? null;
  });

  useEffect(() => {
    let mounted = true;
    const loadProfile = async () => {
      try {
        if (!canLogout) return;
        if (isSuperAdminArea) {
          const res = await superAdminAuthApi.profile();
          const admin = (res as { admin?: unknown }).admin as Record<string, unknown> | undefined;
          if (mounted && admin) {
            setCurrentUser(admin);
            setSuperAdminUser(admin);
          }
        } else {
          const res = await organizationAuthApi.profile();
          const user = (res as { user?: unknown }).user as Record<string, unknown> | undefined;
          if (mounted && user) {
            setCurrentUser(user);
            setOrganizationUser(user);
          }
        }
      } catch {
        // ignore profile load errors in layout
      }
    };
    void loadProfile();
    return () => {
      mounted = false;
    };
  }, [canLogout, isSuperAdminArea]);

  useEffect(() => {
    setProfileForm({
      name: String(currentUser?.name ?? ""),
      email: String(currentUser?.email ?? ""),
      phone: String(currentUser?.phone ?? ""),
      job_title: String(currentUser?.job_title ?? ""),
      password: "",
    });
  }, [currentUser]);

  const handleDashboardLogout = async () => {
    if (isSuperAdminArea) {
      try {
        await superAdminAuthApi.logout();
      } catch {
        /* ignore */
      }
      clearSuperAdminToken();
      clearSuperAdminUser();
      navigate("/super-admin/login");
      return;
    }

    try {
      await organizationAuthApi.logout();
    } catch {
      /* ignore */
    }
    clearOrganizationToken();
    clearOrganizationUser();
    navigate("/dashboard-login");
  };

  const saveProfile = async () => {
    if (!profileForm.name.trim() || !profileForm.email.trim()) {
      toast({ title: "Name and email are required", variant: "destructive" });
      return;
    }
    setSavingProfile(true);
    try {
      if (isSuperAdminArea) {
        const res = await superAdminAuthApi.updateProfile({
          name: profileForm.name.trim(),
          email: profileForm.email.trim(),
          ...(profileForm.password.trim() ? { password: profileForm.password.trim() } : {}),
        });
        const admin = (res as { admin?: unknown }).admin as Record<string, unknown> | undefined;
        if (admin) {
          setCurrentUser(admin);
          setSuperAdminUser(admin);
        }
      } else {
        const res = await organizationAuthApi.updateProfile({
          name: profileForm.name.trim(),
          email: profileForm.email.trim(),
          phone: profileForm.phone.trim() || undefined,
          job_title: profileForm.job_title.trim() || undefined,
          ...(profileForm.password.trim() ? { password: profileForm.password.trim() } : {}),
        });
        const user = (res as { user?: unknown }).user as Record<string, unknown> | undefined;
        if (user) {
          setCurrentUser(user);
          setOrganizationUser(user);
        }
      }
      setEditOpen(false);
      toast({ title: "Profile updated" });
    } catch (err) {
      const error = err as { message?: string; errors?: Record<string, string[]> };
      const first = error.errors ? Object.values(error.errors).flat()[0] : null;
      toast({ title: "Failed to update profile", description: first || error.message || "Unknown error", variant: "destructive" });
    } finally {
      setSavingProfile(false);
    }
  };

  const initials = String(currentUser?.name ?? "U")
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join("");

  const visibleItems = useMemo(() => {
    const canSee = (item: DashboardItem) =>
      isSuperAdminArea
        ? true
        : hasOrganizationAccess(currentUser, {
            requiredPermissions: item.requiredPermissions,
            permissionPrefixes: item.permissionPrefixes,
          });

    return items
      .map((item) => {
        const visibleChildren = (item.children ?? []).filter(canSee);
        const canSeeItem = canSee(item);

        if (visibleChildren.length > 0) {
          return { ...item, children: visibleChildren };
        }
        if (!canSeeItem) return null;
        return { ...item, children: undefined };
      })
      .filter(Boolean) as DashboardItem[];
  }, [currentUser, isSuperAdminArea, items]);

  const notificationScope =
    basePath.startsWith("/clinic-dashboard")
      ? "clinic"
      : basePath.startsWith("/lab-dashboard")
        ? "lab"
        : basePath.startsWith("/radiology-dashboard")
          ? "radiology"
          : null;
  const notificationsEnabled =
    !isSuperAdminArea &&
    canLogout &&
    notificationScope !== null &&
    hasOrganizationAccess(currentUser, { permissionPrefixes: ["notifications"] });

  const notificationsPagePath =
    notificationScope === "clinic"
      ? "/clinic-dashboard/notifications"
      : notificationScope === "lab"
        ? "/lab-dashboard/notifications"
        : notificationScope === "radiology"
          ? "/radiology-dashboard/notifications"
          : "/dashboard-login";

  const { data: notificationsData } = useQuery({
    queryKey: ["topbar", "notifications", basePath],
    queryFn: () => {
      if (notificationScope === "clinic") return clinicApi.notifications({ per_page: "10" });
      if (notificationScope === "lab") return labApi.notifications({ per_page: "10" });
      if (notificationScope === "radiology") return radiologyApi.notifications({ per_page: "10" });
      return Promise.resolve({ data: { data: [] as Array<Record<string, unknown>> } });
    },
    enabled: notificationsEnabled,
    refetchInterval: 10000,
  });

  const topBarNotifications = (((notificationsData as { data?: { data?: Array<Record<string, unknown>> } })?.data?.data) ?? [])
    .map((item) => ({
      id: String(item.id ?? ""),
      title: String(item.title ?? "Notification"),
      message: String(item.message ?? ""),
      isRead: Boolean(item.is_read ?? item.read_at),
      createdAt: String(item.created_at ?? ""),
    }))
    .filter((item) => item.id);
  const unreadTopBarCount = topBarNotifications.filter((item) => !item.isRead).length;

  return (
    <div className="min-h-screen flex w-full" dir={dir}>
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
          {!collapsed && <span className="font-bold text-lg truncate">{displayTitle}</span>}
        </div>
        <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
          {visibleItems.map((item) => {
            const childItems = item.children ?? [];
            const childActive = childItems.some((child) => location.pathname.startsWith(child.to));
            const active = location.pathname.startsWith(item.to) || childActive;
            const hasChildren = childItems.length > 0;
            const isOpen = submenuOpen[item.to] ?? childActive;

            if (hasChildren && !collapsed) {
              return (
                <div key={item.to}>
                  <button
                    type="button"
                    onClick={() => setSubmenuOpen((prev) => ({ ...prev, [item.to]: !(prev[item.to] ?? childActive) }))}
                    className={cn(
                      "w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors",
                      active
                        ? "bg-sidebar-accent text-primary"
                        : "text-muted-foreground hover:text-foreground hover:bg-muted",
                    )}
                  >
                    <span className="flex items-center gap-3">
                      <item.icon className="h-4 w-4 shrink-0" />
                      <span>{item.labelKey ? t(item.labelKey) : item.label}</span>
                    </span>
                    <ChevronDown className={cn("h-4 w-4 transition-transform", isOpen ? "rotate-180" : "")} />
                  </button>
                  {isOpen && (
                    <div className="mt-1 ms-6 space-y-1 border-s ps-3">
                      {childItems.map((child) => {
                        const childIsActive = location.pathname.startsWith(child.to);
                        return (
                          <Link
                            key={child.to}
                            to={child.to}
                            className={cn(
                              "flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors",
                              childIsActive
                                ? "bg-sidebar-accent text-primary font-medium"
                                : "text-muted-foreground hover:text-foreground hover:bg-muted",
                            )}
                          >
                            <child.icon className="h-4 w-4 shrink-0" />
                            <span>{child.labelKey ? t(child.labelKey) : child.label}</span>
                          </Link>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            }

            return (
              <Link
                key={item.to}
                to={item.to}
                className={cn(
                  "flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors",
                  active
                    ? "bg-sidebar-accent text-primary"
                    : "text-muted-foreground hover:text-foreground hover:bg-muted"
                )}
                title={collapsed ? (item.labelKey ? t(item.labelKey) : item.label) : undefined}
              >
                <item.icon className="h-4 w-4 shrink-0" />
                {!collapsed && <span>{item.labelKey ? t(item.labelKey) : item.label}</span>}
              </Link>
            );
          })}
        </nav>
        <div className="p-3 border-t space-y-1">
          {canLogout && (
            <button
              type="button"
              onClick={handleDashboardLogout}
              className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-muted-foreground hover:text-foreground hover:bg-muted w-full"
            >
              <LogOut className="h-4 w-4 shrink-0" />
              {!collapsed && <span>{t("auth.logout")}</span>}
            </button>
          )}
          <Link to="/" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-muted-foreground hover:text-foreground hover:bg-muted">
            <Home className="h-4 w-4 shrink-0" />
            {!collapsed && <span>Back to Home</span>}
          </Link>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-16 flex justify-between items-center gap-4 px-4 md:px-6 border-b bg-card">
         <div className="flex items-center gap-4">
 	<button onClick={() => setCollapsed(!collapsed)} className="p-2 rounded-lg hover:bg-muted hidden md:block" aria-label="Toggle sidebar">
            <Menu className="h-5 w-5" />
          </button>
          <h1 className="text-lg font-semibold">{displayTitle}</h1>
	</div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" className="text-xs" onClick={toggleLanguage}>
              {lang === "en" ? t("lang.ar") : t("lang.en")}
            </Button>
            {notificationsEnabled && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="outline" size="icon" className="relative" aria-label="Notifications">
                    <Bell className="h-4 w-4" />
                    {unreadTopBarCount > 0 && (
                      <span className="absolute -top-1 -right-1 h-5 min-w-5 px-1 rounded-full bg-destructive text-[10px] font-semibold text-destructive-foreground flex items-center justify-center">
                        {unreadTopBarCount > 99 ? "99+" : unreadTopBarCount}
                      </span>
                    )}
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-80">
                  <DropdownMenuLabel className="flex items-center justify-between">
                    <span>Notifications</span>
                    <span className="text-xs text-muted-foreground">{unreadTopBarCount} unread</span>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  {topBarNotifications.length === 0 && (
                    <div className="px-2 py-3 text-xs text-muted-foreground">No notifications yet.</div>
                  )}
                  {topBarNotifications.map((item) => (
                    <DropdownMenuItem key={item.id} onSelect={() => navigate(notificationsPagePath)} className="items-start py-2">
                      <div className="space-y-1">
                        <p className="text-xs font-medium leading-none">{item.title}</p>
                        <p className="text-xs text-muted-foreground line-clamp-2">{item.message || "Open notifications page to view details."}</p>
                        {item.createdAt && <p className="text-[10px] text-muted-foreground">{new Date(item.createdAt).toLocaleString()}</p>}
                      </div>
                    </DropdownMenuItem>
                  ))}
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onSelect={() => navigate(notificationsPagePath)}>
                    View all notifications
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
            {canLogout && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="outline" size="sm" className="gap-2">
                    <Avatar className="h-6 w-6">
                      <AvatarFallback className="text-xs">{initials || "U"}</AvatarFallback>
                    </Avatar>
                    <span className="max-w-[140px] truncate text-xs">{String(currentUser?.name ?? "User")}</span>
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-64">
                  <DropdownMenuLabel className="space-y-0.5">
                    <p className="font-medium">{String(currentUser?.name ?? "User")}</p>
                    <p className="text-xs text-muted-foreground font-normal">{String(currentUser?.email ?? "")}</p>
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={() => setViewOpen(true)}>
                    <User className="h-4 w-4 mr-2" />
                    User Data
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => setEditOpen(true)}>
                    <Settings className="h-4 w-4 mr-2" />
                    Update Profile
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={handleDashboardLogout}>
                    <LogOut className="h-4 w-4 mr-2" />
                    {t("auth.logout")}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </header>

        <main className="flex-1 p-4 md:p-6 overflow-auto">
          <Outlet />
        </main>
      </div>

      <Dialog open={viewOpen} onOpenChange={setViewOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader><DialogTitle>User Data</DialogTitle></DialogHeader>
          <div className="space-y-2 text-sm">
            <p><span className="font-medium">Name:</span> {String(currentUser?.name ?? "—")}</p>
            <p><span className="font-medium">Email:</span> {String(currentUser?.email ?? "—")}</p>
            {!isSuperAdminArea && <p><span className="font-medium">Phone:</span> {String(currentUser?.phone ?? "—")}</p>}
            {!isSuperAdminArea && <p><span className="font-medium">Job Title:</span> {String(currentUser?.job_title ?? "—")}</p>}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setViewOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader><DialogTitle>Update Profile</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1.5">
              <Label>Name</Label>
              <Input value={profileForm.name} onChange={(e) => setProfileForm((f) => ({ ...f, name: e.target.value }))} />
            </div>
            <div className="space-y-1.5">
              <Label>Email</Label>
              <Input type="email" value={profileForm.email} onChange={(e) => setProfileForm((f) => ({ ...f, email: e.target.value }))} />
            </div>
            {!isSuperAdminArea && (
              <div className="space-y-1.5">
                <Label>Phone</Label>
                <Input value={profileForm.phone} onChange={(e) => setProfileForm((f) => ({ ...f, phone: e.target.value }))} />
              </div>
            )}
            {!isSuperAdminArea && (
              <div className="space-y-1.5">
                <Label>Job Title</Label>
                <Input value={profileForm.job_title} onChange={(e) => setProfileForm((f) => ({ ...f, job_title: e.target.value }))} />
              </div>
            )}
            <div className="space-y-1.5">
              <Label>New Password (optional)</Label>
              <Input type="password" value={profileForm.password} onChange={(e) => setProfileForm((f) => ({ ...f, password: e.target.value }))} />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditOpen(false)}>Cancel</Button>
            <Button onClick={saveProfile} disabled={savingProfile}>{savingProfile ? "Saving..." : "Save"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

// Clinic Dashboard
const clinicItems: DashboardItem[] = [
  { label: "Dashboard", labelKey: "clinic.menu.dashboard", to: "/clinic-dashboard", icon: LayoutDashboard },
 {
    label: "Users Management",
    labelKey: "clinic.menu.users_management",
    to: "/clinic-dashboard/users",
    icon: UserCog,
    permissionPrefixes: ["users"],
    children: [
      { label: "Users", labelKey: "clinic.menu.users", to: "/clinic-dashboard/users", icon: UserCog, permissionPrefixes: ["users"] },
      { label: "Roles", labelKey: "clinic.menu.roles", to: "/clinic-dashboard/roles", icon: Shield, permissionPrefixes: ["roles", "permissions"] },
    ],
  },  
{
    label: "Reservations Management",
    labelKey: "clinic.menu.reservations_management",
    to: "/clinic-dashboard/reservations",
    icon: CalendarDays,
    permissionPrefixes: ["reservations"],
    children: [
      { label: "Reservations", labelKey: "clinic.menu.reservations", to: "/clinic-dashboard/reservations", icon: List, permissionPrefixes: ["reservations"] },
      { label: "Today", labelKey: "clinic.menu.today", to: "/clinic-dashboard/today", icon: Clock, permissionPrefixes: ["reservations"] },
      { label: "Reservation Numbers", labelKey: "clinic.menu.numbers", to: "/clinic-dashboard/numbers", icon: Hash, permissionPrefixes: ["reservation-numbers", "numbers"] },
      { label: "Reservation Slots", labelKey: "clinic.menu.slots", to: "/clinic-dashboard/slots", icon: Clock, permissionPrefixes: ["reservation-slots", "slots"] },
    ],
  },
// clinic management
  { label: "Clinic Management", labelKey: "clinic.menu.clinic_management", to: "/clinic-dashboard/clinic", icon: Stethoscope, permissionPrefixes: ["clinic"],
    children: [
  { label: "Services", labelKey: "clinic.menu.services", to: "/clinic-dashboard/services", icon: Stethoscope, permissionPrefixes: ["services"] },
  { label: "Settings", labelKey: "clinic.menu.settings", to: "/clinic-dashboard/settings", icon: Settings, permissionPrefixes: ["settings"] },
  { label: "Inventory", labelKey: "clinic.menu.inventory", to: "/clinic-dashboard/inventory", icon: Boxes, permissionPrefixes: ["inventory"] },
  { label: "Reviews", labelKey: "clinic.menu.reviews", to: "/clinic-dashboard/reviews", icon: Star, permissionPrefixes: ["reviews"] },
  { label: "Announcements", labelKey: "clinic.menu.announcements", to: "/clinic-dashboard/announcements", icon: Bell, permissionPrefixes: ["announcements"] },
    ],
  },
  { label: "Doctors", labelKey: "clinic.menu.doctors", to: "/clinic-dashboard/doctors", icon: Stethoscope, permissionPrefixes: ["doctors"] },
  { label: "Patients", labelKey: "clinic.menu.patients", to: "/clinic-dashboard/patients", icon: Users, permissionPrefixes: ["patients"] },
  { label: "Chat", labelKey: "clinic.menu.chat", to: "/clinic-dashboard/chat", icon: MessageCircle, permissionPrefixes: ["chat", "chats"] },
  { label: "Notifications", labelKey: "clinic.menu.notifications", to: "/clinic-dashboard/notifications", icon: Bell, permissionPrefixes: ["notifications"] },
  { label: "Financial", labelKey: "clinic.menu.financial", to: "/clinic-dashboard/financial", icon: FileText, permissionPrefixes: ["financial"] },
//   { label: "Modules", labelKey: "clinic.menu.modules", to: "/clinic-dashboard/modules", icon: Puzzle, permissionPrefixes: ["modules"] },
  { label: "Trash", labelKey: "clinic.menu.trash", to: "/clinic-dashboard/trash", icon: Trash2 },
];

export function ClinicLayout() {
  return (
    <DashboardLayout
      title="Clinic Dashboard"
      titleKey="clinic.title"
      basePath="/clinic-dashboard"
      items={clinicItems}
    />
  );
}

// Admin Dashboard
const adminItems: DashboardItem[] = [
  { label: "Dashboard", labelKey: "admin.menu.dashboard", to: "/super-admin", icon: LayoutDashboard },
  { label: "Clinics", labelKey: "admin.menu.clinics", to: "/super-admin/clinics", icon: Stethoscope },
  { label: "Medical Labs", labelKey: "admin.menu.labs", to: "/super-admin/labs", icon: Stethoscope },
  { label: "Radiology Centers", labelKey: "admin.menu.radiology", to: "/super-admin/radiology", icon: Stethoscope },
  { label: "Specialties", labelKey: "admin.menu.specialties", to: "/super-admin/specialties", icon: Stethoscope },
  { label: "Locations", labelKey: "admin.menu.locations", to: "/super-admin/locations", icon: Stethoscope },
  { label: "Users & Roles", labelKey: "admin.menu.users", to: "/super-admin/users", icon: UserCog },
  { label: "Reviews", labelKey: "admin.menu.reviews", to: "/super-admin/reviews", icon: Stethoscope },
  { label: "Announcements", labelKey: "admin.menu.announcements", to: "/super-admin/announcements", icon: Stethoscope },
  { label: "Financial", labelKey: "admin.menu.financial", to: "/super-admin/financial", icon: FileText },
];

export function AdminLayout() {
  return (
    <DashboardLayout
      title="Admin Dashboard"
      titleKey="admin.title"
      basePath="/super-admin"
      items={adminItems}
    />
  );
}

// Lab Dashboard
const labItems: DashboardItem[] = [
  { label: "Dashboard", labelKey: "lab.menu.dashboard", to: "/lab-dashboard", icon: LayoutDashboard },
//   { label: "Reservations", labelKey: "lab.menu.reservations", to: "/lab-dashboard/reservations", icon: CalendarDays },
{
    label: "Users Management",
    labelKey: "lab.menu.users_management",
    to: "/lab-dashboard/users",
    icon: UserCog,
    permissionPrefixes: ["users"],
    children: [
      { label: "Users", labelKey: "lab.menu.users", to: "/lab-dashboard/users", icon: UserCog, permissionPrefixes: ["users"] },
      { label: "Roles", labelKey: "lab.menu.roles", to: "/lab-dashboard/roles", icon: Shield, permissionPrefixes: ["roles", "permissions"] },
    ],
  },  
{
    label: "Medical Analyses Management",
    labelKey: "lab.menu.medical_analyses_management",
    to: "/lab-dashboard/medical-analyses",
    icon: FileText,
    permissionPrefixes: ["medical-analyses"],
    children: [
 { label: "Service Categories", labelKey: "lab.menu.service_categories", to: "/lab-dashboard/service-categories", icon: Boxes, permissionPrefixes: ["service-categories"] },
  { label: "Services", labelKey: "lab.menu.services", to: "/lab-dashboard/services", icon: Stethoscope, permissionPrefixes: ["services"] },

      { label: "Medical Analyses", labelKey: "lab.menu.medical_analyses", to: "/lab-dashboard/medical-analyses", icon: FileText, permissionPrefixes: ["medical-analyses"] },
      { label: "Today Medical Analyses", labelKey: "lab.menu.today_medical_analyses", to: "/lab-dashboard/today-medical-analyses", icon: Clock, permissionPrefixes: ["medical-analyses"] },
    ],
  },  
{ label: "Patients", labelKey: "lab.menu.patients", to: "/lab-dashboard/patients", icon: Users, permissionPrefixes: ["patients"] },
  { label: "Chat", to: "/lab-dashboard/chat", labelKey: "lab.menu.chat", icon: MessageCircle, permissionPrefixes: ["chat", "chats"] },
  { label: "Notifications", labelKey: "lab.menu.notifications", to: "/lab-dashboard/notifications", icon: Bell, permissionPrefixes: ["notifications"] },
   { label: "Financial", labelKey: "lab.menu.financial", to: "/lab-dashboard/financial", icon: FileText, permissionPrefixes: ["financial"] },
];

export function LabLayout() {
  return (
    <DashboardLayout
      title="Lab Dashboard"
      titleKey="lab.title"
      basePath="/lab-dashboard"
      items={labItems}
    />
  );
}

// Radiology Dashboard
const radioItems: DashboardItem[] = [
  { label: "Dashboard", labelKey: "radiology.menu.dashboard", to: "/radiology-dashboard", icon: LayoutDashboard },
  { label: "Rays", labelKey: "radiology.menu.rays", to: "/radiology-dashboard/rays", icon: CalendarDays, permissionPrefixes: ["rays"] },
  { label: "Ray Categories", labelKey: "radiology.menu.ray_categories", to: "/radiology-dashboard/ray-categories", icon: Boxes, permissionPrefixes: ["ray-categories"] },
  { label: "Users", labelKey: "radiology.menu.users", to: "/radiology-dashboard/users", icon: UserCog, permissionPrefixes: ["users"] },
  { label: "Patients", labelKey: "radiology.menu.patients", to: "/radiology-dashboard/patients", icon: Users, permissionPrefixes: ["patients"] },
  { label: "Chat", to: "/radiology-dashboard/chat", icon: MessageCircle, permissionPrefixes: ["chat", "chats"] },
  { label: "Notifications", labelKey: "radiology.menu.notifications", to: "/radiology-dashboard/notifications", icon: Bell, permissionPrefixes: ["notifications"] },
  { label: "Roles", labelKey: "radiology.menu.roles", to: "/radiology-dashboard/roles", icon: Shield, permissionPrefixes: ["roles", "permissions"] },
  { label: "Financial", labelKey: "radiology.menu.financial", to: "/radiology-dashboard/financial", icon: FileText, permissionPrefixes: ["financial"] },
];

export function RadiologyLayout() {
  return (
    <DashboardLayout
      title="Radiology Dashboard"
      titleKey="radiology.title"
      basePath="/radiology-dashboard"
      items={radioItems}
    />
  );
}
