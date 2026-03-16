import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";

export default function ClinicAnnouncements() {
  const { t } = useLanguage();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<Array<{ id: string | number; title?: string; content?: string; audience?: string; channel?: string; createdAt?: string; status?: string }>>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({ title: "", content: "", audience: "all", channel: "in-app", status: "draft" });
  const { toast } = useToast();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "announcements"],
    queryFn: () => clinicApi.announcements(),
  });
  const announcements = useMemo<Array<{ id: string | number; title?: string; content?: string; audience?: string; channel?: string; createdAt?: string; status?: string }>>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: Array<{ id: string | number; title?: string; content?: string; audience?: string; channel?: string; createdAt?: string; status?: string }> })?.data ?? [];
  }, [data]);
  useEffect(() => setItems(announcements), [announcements]);
  const filtered = items.filter((a) => (a.title ?? "").toLowerCase().includes(search.toLowerCase()));
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const audienceLabels: Record<string, string> = {
    patients: "Patients",
    staff: "Staff",
    all: "Patients & Staff",
  };

  const statusStyles: Record<string, string> = {
    draft: "bg-muted text-muted-foreground",
    scheduled: "bg-warning/10 text-warning",
    sent: "bg-success/10 text-success",
  };

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ title: "", content: "", audience: "all", channel: "in-app", status: "draft" });
  };
  const openShow = (a: { id: string | number; title?: string; content?: string; audience?: string; channel?: string; createdAt?: string; status?: string }) => {
    setDialogMode("show");
    setActiveId(String(a.id));
    setForm({ title: a.title ?? "", content: a.content ?? "", audience: a.audience ?? "all", channel: a.channel ?? "in-app", status: a.status ?? "draft" });
  };
  const openEdit = (a: { id: string | number; title?: string; content?: string; audience?: string; channel?: string; createdAt?: string; status?: string }) => {
    setDialogMode("edit");
    setActiveId(String(a.id));
    setForm({ title: a.title ?? "", content: a.content ?? "", audience: a.audience ?? "all", channel: a.channel ?? "in-app", status: a.status ?? "draft" });
  };
  const onSave = () => {
    if (!form.title.trim()) {
      toast({ title: t("clinic.announcements.title_is_required"), variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      setItems((prev) => [{ id: `local-${Date.now()}`, title: form.title, content: form.content, audience: form.audience, channel: form.channel, status: form.status, createdAt: new Date().toISOString().slice(0, 10) }, ...prev]);
      toast({ title: t("clinic.announcements.announcement_added") });
    } else if (dialogMode === "edit") {
      setItems((prev) => prev.map((a) => (String(a.id) === activeId ? { ...a, title: form.title, content: form.content, audience: form.audience, channel: form.channel, status: form.status } : a)));
      toast({ title: t("clinic.announcements.announcement_updated") });
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{t("clinic.announcements.title")}</h2>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2"><Plus className="h-4 w-4" />{t("clinic.announcements.add")}</Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder={t("clinic.announcements.search")}
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          className="pl-10"
        />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.announcements.title")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.announcements.audience")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.announcements.channel")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.announcements.status")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("clinic.announcements.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={6}>{t("clinic.announcements.loading_announcements")}</td></tr>}
              {error && <tr><td className="p-4 text-destructive" colSpan={6}>{error instanceof Error ? error.message : t("clinic.announcements.failed_to_load_announcements")}</td></tr>}
              {!isLoading && !error && paged.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={6}>{t("clinic.announcements.no_announcements_found")}</td></tr>}
              {paged.map((a) => (
                <tr key={String(a.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(a.id)}</td>
                  <td className="p-4 font-medium">{a.title ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{audienceLabels[a.audience ?? "all"] ?? (a.audience ?? "all")}</td>
                  <td className="p-4 text-muted-foreground">{(a.channel ?? "in-app").toUpperCase()}</td>
                  <td className="p-4"><Badge variant="secondary" className={statusStyles[a.status ?? ""] ?? ""}>{a.status ?? "—"}</Badge></td>
                  <td className="p-4"><div className="flex gap-2"><Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(a)}><Eye className="h-4 w-4" />Show</Button><Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(a)}><Edit className="h-4 w-4" />Edit</Button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">{t("clinic.announcements.page")} {safePage} {t("clinic.announcements.of")} {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>{t("clinic.announcements.previous")}</Button>
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={safePage >= totalPages}>{t("clinic.announcements.next")}</Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>{dialogMode === "add" ? t("clinic.announcements.add") : dialogMode === "edit" ? t("clinic.announcements.edit") : t("clinic.announcements.details")}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2"><Label>{t("clinic.announcements.title")}</Label><Input value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} disabled={dialogMode === "show"} /></div>
            <div className="space-y-2"><Label>{t("clinic.announcements.content")}</Label><Input value={form.content} onChange={(e) => setForm((f) => ({ ...f, content: e.target.value }))} disabled={dialogMode === "show"} /></div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2"><Label>{t("clinic.announcements.audience")}</Label><Input value={form.audience} onChange={(e) => setForm((f) => ({ ...f, audience: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="space-y-2"><Label>{t("clinic.announcements.channel")}</Label><Input value={form.channel} onChange={(e) => setForm((f) => ({ ...f, channel: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="space-y-2"><Label>{t("clinic.announcements.status")}</Label><Input value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))} disabled={dialogMode === "show"} /></div>
            </div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setDialogMode(null)}>{t("clinic.announcements.close")}</Button>{dialogMode !== "show" && <Button onClick={onSave}>{t("clinic.announcements.save")}</Button>}</DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

