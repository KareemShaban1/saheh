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

export default function ClinicChat() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<Array<{ id: string | number; patient?: string; lastMessage?: string; updatedAt?: string; unread?: number; channel?: string }>>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({ patient: "", lastMessage: "", channel: "in-app", unread: "0", updatedAt: "" });
  const { toast } = useToast();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "chats"],
    queryFn: () => clinicApi.chats(),
  });

  const conversations = useMemo<Array<{ id: string | number; patient?: string; lastMessage?: string; updatedAt?: string; unread?: number; channel?: string }>>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return Array.isArray(root) ? (root as Array<{ id: string | number; patient?: string; lastMessage?: string; updatedAt?: string; unread?: number; channel?: string }>) : [];
  }, [data]);
  useEffect(() => setItems(conversations), [conversations]);
  const filtered = items.filter((c) =>
    `${c.patient ?? ""} ${c.lastMessage ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ patient: "", lastMessage: "", channel: "in-app", unread: "0", updatedAt: "" });
  };
  const openShow = (c: { id: string | number; patient?: string; lastMessage?: string; updatedAt?: string; unread?: number; channel?: string }) => {
    setDialogMode("show");
    setActiveId(String(c.id));
    setForm({ patient: c.patient ?? "", lastMessage: c.lastMessage ?? "", channel: c.channel ?? "in-app", unread: String(c.unread ?? 0), updatedAt: c.updatedAt ?? "" });
  };
  const openEdit = (c: { id: string | number; patient?: string; lastMessage?: string; updatedAt?: string; unread?: number; channel?: string }) => {
    setDialogMode("edit");
    setActiveId(String(c.id));
    setForm({ patient: c.patient ?? "", lastMessage: c.lastMessage ?? "", channel: c.channel ?? "in-app", unread: String(c.unread ?? 0), updatedAt: c.updatedAt ?? "" });
  };
  const onSave = () => {
    if (!form.patient.trim()) {
      toast({ title: "Patient is required", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      setItems((prev) => [{ id: `local-${Date.now()}`, patient: form.patient, lastMessage: form.lastMessage, channel: form.channel, unread: Number(form.unread || 0), updatedAt: form.updatedAt || new Date().toISOString().slice(0, 16).replace("T", " ") }, ...prev]);
      toast({ title: "Conversation added" });
    } else if (dialogMode === "edit") {
      setItems((prev) => prev.map((c) => (String(c.id) === activeId ? { ...c, patient: form.patient, lastMessage: form.lastMessage, channel: form.channel, unread: Number(form.unread || 0), updatedAt: form.updatedAt } : c)));
      toast({ title: "Conversation updated" });
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Chat & Messages</h2>
          <p className="text-muted-foreground text-sm mt-1">Table with add, show, and edit dialogs</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2"><Plus className="h-4 w-4" />Add Conversation</Button>
      </div>
      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="pl-10" placeholder="Search conversations..." />
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Patient</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Last Message</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Channel</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Updated</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Unread</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={7}>Loading chats...</td></tr>}
              {error && <tr><td className="p-4 text-destructive" colSpan={7}>{error instanceof Error ? error.message : "Failed to load chats"}</td></tr>}
              {!isLoading && !error && paged.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={7}>No conversations found.</td></tr>}
              {paged.map((c) => (
                <tr key={String(c.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(c.id)}</td>
                  <td className="p-4 font-medium">{c.patient ?? "—"}</td>
                  <td className="p-4 text-muted-foreground max-w-[280px] truncate">{c.lastMessage ?? ""}</td>
                  <td className="p-4"><Badge variant="outline">{c.channel ?? "in-app"}</Badge></td>
                  <td className="p-4 text-muted-foreground">{c.updatedAt ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{c.unread ?? 0}</td>
                  <td className="p-4"><div className="flex gap-2"><Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(c)}><Eye className="h-4 w-4" />Show</Button><Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(c)}><Edit className="h-4 w-4" />Edit</Button></div></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">Page {safePage} of {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>Previous</Button>
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.min(totalPages, p + 1))} disabled={safePage >= totalPages}>Next</Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>{dialogMode === "add" ? "Add Conversation" : dialogMode === "edit" ? "Edit Conversation" : "Conversation Details"}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2"><Label>Patient</Label><Input value={form.patient} onChange={(e) => setForm((f) => ({ ...f, patient: e.target.value }))} disabled={dialogMode === "show"} /></div>
            <div className="space-y-2"><Label>Last Message</Label><Input value={form.lastMessage} onChange={(e) => setForm((f) => ({ ...f, lastMessage: e.target.value }))} disabled={dialogMode === "show"} /></div>
            <div className="grid sm:grid-cols-3 gap-4">
              <div className="space-y-2"><Label>Channel</Label><Input value={form.channel} onChange={(e) => setForm((f) => ({ ...f, channel: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="space-y-2"><Label>Unread</Label><Input type="number" value={form.unread} onChange={(e) => setForm((f) => ({ ...f, unread: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="space-y-2"><Label>Updated</Label><Input value={form.updatedAt} onChange={(e) => setForm((f) => ({ ...f, updatedAt: e.target.value }))} disabled={dialogMode === "show"} /></div>
            </div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setDialogMode(null)}>Close</Button>{dialogMode !== "show" && <Button onClick={onSave}>Save</Button>}</DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
