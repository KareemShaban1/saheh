import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Bell, CheckCheck, CircleAlert, CreditCard, FlaskConical, Search, ShieldAlert, Stethoscope, XCircle } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi, labApi, radiologyApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

type Scope = "clinic" | "lab" | "radiology";

type NotificationType =
  | "new_appointment"
  | "pending_reservation"
  | "cancelled_reservation"
  | "payment_pending"
  | "service_completed"
  | "announcement";

type Priority = "high" | "medium" | "low";

type NotificationItem = {
  id: string;
  type: NotificationType;
  priority: Priority;
  module: string;
  title: string;
  message: string;
  timestamp: string;
  isRead?: boolean;
};

const priorityStyles: Record<Priority, string> = {
  high: "bg-destructive/10 text-destructive",
  medium: "bg-warning/10 text-warning",
  low: "bg-muted text-muted-foreground",
};

const typeStyles: Record<NotificationType, string> = {
  new_appointment: "bg-primary/10 text-primary",
  pending_reservation: "bg-warning/10 text-warning",
  cancelled_reservation: "bg-destructive/10 text-destructive",
  payment_pending: "bg-orange-500/10 text-orange-600",
  service_completed: "bg-success/10 text-success",
  announcement: "bg-secondary/20 text-secondary-foreground",
};

const typeLabels: Record<NotificationType, string> = {
  new_appointment: "New Appointment",
  pending_reservation: "Pending Reservation",
  cancelled_reservation: "Cancelled Reservation",
  payment_pending: "Payment Pending",
  service_completed: "Service Completed",
  announcement: "Announcement",
};

const coerceArray = (payload: unknown): Array<Record<string, unknown>> => {
  if (Array.isArray(payload)) return payload as Array<Record<string, unknown>>;
  const root = (payload as { data?: unknown })?.data ?? payload;
  if (Array.isArray(root)) return root as Array<Record<string, unknown>>;
  return ((root as { data?: unknown })?.data as Array<Record<string, unknown>>) ?? [];
};

const pickText = (row: Record<string, unknown>, keys: string[], fallback = "—") => {
  for (const key of keys) {
    const value = row[key];
    if (typeof value === "string" && value.trim()) return value.trim();
    if (typeof value === "number") return String(value);
  }
  return fallback;
};

const parseToIso = (dateText: string, timeText?: string) => {
  if (!dateText || dateText === "—") return new Date(0).toISOString();
  const isoCandidate = `${dateText}${timeText && timeText !== "—" ? ` ${timeText}` : ""}`.trim();
  const parsed = new Date(isoCandidate);
  if (!Number.isNaN(parsed.getTime())) return parsed.toISOString();
  return new Date(0).toISOString();
};

const toBool = (value: unknown): boolean => {
  if (typeof value === "boolean") return value;
  if (typeof value === "number") return value === 1;
  if (typeof value === "string") {
    const normalized = value.toLowerCase().trim();
    return normalized === "1" || normalized === "true" || normalized === "read";
  }
  return false;
};

const inferType = (row: Record<string, unknown>): NotificationType => {
  const raw = pickText(row, ["type", "event", "category", "code", "action"], "").toLowerCase();
  if (raw.includes("cancel")) return "cancelled_reservation";
  if (raw.includes("pending") || raw.includes("waiting")) return "pending_reservation";
  if (raw.includes("payment") || raw.includes("invoice")) return "payment_pending";
  if (raw.includes("complete") || raw.includes("finish") || raw.includes("result")) return "service_completed";
  if (raw.includes("announce") || raw.includes("news")) return "announcement";
  if (raw.includes("appoint") || raw.includes("reservation") || raw.includes("booking")) return "new_appointment";
  return "new_appointment";
};

const inferPriority = (row: Record<string, unknown>, type: NotificationType): Priority => {
  const raw = pickText(row, ["priority", "importance", "level", "severity"], "").toLowerCase();
  if (raw === "high" || raw === "urgent" || raw === "critical") return "high";
  if (raw === "medium" || raw === "normal") return "medium";
  if (raw === "low") return "low";
  if (type === "cancelled_reservation" || type === "pending_reservation") return "high";
  if (type === "payment_pending" || type === "new_appointment") return "medium";
  return "low";
};

const inferModule = (row: Record<string, unknown>, fallback = "general") => {
  const module = pickText(row, ["module", "module_name", "domain", "feature", "section"], fallback);
  return module.toLowerCase().replaceAll(" ", "_");
};

const iconForType = (type: NotificationType) => {
  switch (type) {
    case "new_appointment":
      return <Bell className="h-4 w-4 text-primary" />;
    case "pending_reservation":
      return <CircleAlert className="h-4 w-4 text-warning" />;
    case "cancelled_reservation":
      return <XCircle className="h-4 w-4 text-destructive" />;
    case "payment_pending":
      return <CreditCard className="h-4 w-4 text-orange-600" />;
    case "service_completed":
      return <CheckCheck className="h-4 w-4 text-success" />;
    case "announcement":
      return <ShieldAlert className="h-4 w-4 text-secondary-foreground" />;
    default:
      return <Bell className="h-4 w-4" />;
  }
};

const scopeLabel: Record<Scope, string> = {
  clinic: "Clinic",
  lab: "Lab",
  radiology: "Radiology Center",
};

export default function OrganizationNotificationsPage({ scope }: { scope: Scope }) {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [typeFilter, setTypeFilter] = useState<"all" | NotificationType>("all");
  const [priorityFilter, setPriorityFilter] = useState<"all" | Priority>("all");
  const [moduleFilter, setModuleFilter] = useState("all");
  const [showUnreadOnly, setShowUnreadOnly] = useState(false);
  const [readIds, setReadIds] = useState<Set<string>>(new Set());

  const feedQuery = useQuery({
    queryKey: [scope, "notifications", "feed"],
    queryFn: async () => {
      if (scope === "clinic") return clinicApi.notifications({ per_page: "250" });
      if (scope === "lab") return labApi.notifications({ per_page: "250" });
      return radiologyApi.notifications({ per_page: "250" });
    },
    retry: false,
  });

  const backendNotifications = useMemo<NotificationItem[]>(() => {
    const rows = coerceArray(feedQuery.data);
    return rows
      .map((row) => {
        const id = pickText(row, ["id", "uuid", "notification_id"], "");
        if (!id) return null;
        const type = inferType(row);
        const timestamp = parseToIso(pickText(row, ["created_at", "createdAt", "date", "timestamp"], ""), pickText(row, ["time"], ""));
        return {
          id: `${scope}-feed-${id}`,
          type,
          priority: inferPriority(row, type),
          module: inferModule(row),
          title: pickText(row, ["title", "subject", "name"], typeLabels[type]),
          message: pickText(row, ["message", "body", "content", "description"], "—"),
          timestamp,
          isRead: toBool(row.is_read ?? row.read_at ?? row.read),
        } satisfies NotificationItem;
      })
      .filter((item): item is NotificationItem => item !== null)
      .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());
  }, [feedQuery.data, scope]);
  const notifications = backendNotifications;
  const availableModules = Array.from(new Set(notifications.map((item) => item.module))).sort();

  const filtered = notifications.filter((item) => {
    const unread = !item.isRead && !readIds.has(item.id);
    const matchesSearch =
      item.title.toLowerCase().includes(search.toLowerCase()) ||
      item.message.toLowerCase().includes(search.toLowerCase());
    const matchesType = typeFilter === "all" || item.type === typeFilter;
    const matchesPriority = priorityFilter === "all" || item.priority === priorityFilter;
    const matchesModule = moduleFilter === "all" || item.module === moduleFilter;
    const matchesUnread = !showUnreadOnly || unread;
    return matchesSearch && matchesType && matchesPriority && matchesModule && matchesUnread;
  });

  const unreadCount = notifications.filter((item) => !item.isRead && !readIds.has(item.id)).length;
  const isLoading = feedQuery.isLoading;
  const hasBlockingError = feedQuery.error;

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{scopeLabel[scope]} {t("notifications.title")}</h2>
         
        </div>
        <Button
          variant="outline"
          onClick={() => setReadIds(new Set(notifications.map((item) => item.id)))}
          disabled={notifications.length === 0 || unreadCount === 0}
        >
          {t("notifications.mark_all_as_read")} ({unreadCount})
        </Button>
      </div>

      <div className="grid gap-3 md:grid-cols-4 mb-4">
        <div className="relative md:col-span-2">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            className="pl-10"
            placeholder={t("notifications.search")}
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
        <Select value={typeFilter} onValueChange={(value) => setTypeFilter(value as "all" | NotificationType)}>
          <SelectTrigger>
            <SelectValue placeholder={t("notifications.type")} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t("notifications.all_types")}</SelectItem>
            <SelectItem value="new_appointment">{t("notifications.new_appointment")}</SelectItem>
            <SelectItem value="pending_reservation">{t("notifications.pending_reservation")}</SelectItem>
            <SelectItem value="cancelled_reservation">{t("notifications.cancelled_reservation")}</SelectItem>
            <SelectItem value="payment_pending">{t("notifications.payment_pending")}</SelectItem>
            <SelectItem value="service_completed">{t("notifications.service_completed")}</SelectItem>
            <SelectItem value="announcement">{t("notifications.announcement")}</SelectItem>
          </SelectContent>
        </Select>
        <Select value={priorityFilter} onValueChange={(value) => setPriorityFilter(value as "all" | Priority)}>
          <SelectTrigger>
            <SelectValue placeholder={t("notifications.priority")} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t("notifications.all_priorities")}</SelectItem>
            <SelectItem value="high">{t("notifications.high")}</SelectItem>
            <SelectItem value="medium">{t("notifications.medium")}</SelectItem>
            <SelectItem value="low">{t("notifications.low")}</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="mb-4 max-w-xs">
        <Select value={moduleFilter} onValueChange={setModuleFilter}>
          <SelectTrigger>
            <SelectValue placeholder={t("notifications.module")} />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">{t("notifications.all_modules")}</SelectItem>
            {availableModules.map((moduleName) => (
              <SelectItem key={moduleName} value={moduleName}>
                {moduleName.replaceAll("_", " ")}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      <div className="mb-4">
        <Button variant={showUnreadOnly ? "default" : "outline"} size="sm" onClick={() => setShowUnreadOnly((prev) => !prev)}>
          {showUnreadOnly ? t("notifications.showing_unread_only") : t("notifications.show_unread_only")}
        </Button>
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="divide-y">
          {isLoading && <div className="p-4 text-muted-foreground text-sm">{t("notifications.loading_notifications")}</div>}
          {hasBlockingError && (
            <div className="p-4 text-destructive text-sm">
              {feedQuery.error instanceof Error ? feedQuery.error.message : t("notifications.failed_to_load_notifications")}
            </div>
          )}
          {!isLoading && !hasBlockingError && filtered.length === 0 && (
            <div className="p-4 text-muted-foreground text-sm">{t("notifications.no_notifications_found")}</div>
          )}
          {filtered.map((item) => {
            const unread = !item.isRead && !readIds.has(item.id);
            return (
              <div key={item.id} className={`p-4 flex items-start gap-3 ${unread ? "bg-primary/5" : ""}`}>
                <div className="h-9 w-9 rounded-full bg-sidebar-accent flex items-center justify-center shrink-0">
                  {item.type === "service_completed" && scope === "lab" ? (
                    <FlaskConical className="h-4 w-4 text-success" />
                  ) : item.type === "service_completed" && scope === "clinic" ? (
                    <Stethoscope className="h-4 w-4 text-success" />
                  ) : (
                    iconForType(item.type)
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <p className="font-medium text-sm">{item.title}</p>
                    <Badge variant="secondary" className={typeStyles[item.type]}>{typeLabels[item.type]}</Badge>
                    <Badge variant="secondary" className={priorityStyles[item.priority]}>{item.priority}</Badge>
                    <Badge variant="outline">{item.module.replaceAll("_", " ")}</Badge>
                    {unread && <Badge variant="secondary" className="bg-primary/10 text-primary">{t("notifications.unread")}</Badge>}
                  </div>
                  <p className="text-sm text-muted-foreground mt-1">{item.message}</p>
                  <p className="text-xs text-muted-foreground mt-1">{new Date(item.timestamp).toLocaleString()}</p>
                </div>
                {unread && (
                  <Button variant="ghost" size="sm" onClick={() => setReadIds((prev) => new Set(prev).add(item.id))}>
                    {t("notifications.mark_read")}
                  </Button>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
