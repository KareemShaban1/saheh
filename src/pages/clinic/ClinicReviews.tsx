import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search, Star } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

export default function ClinicReviews() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<Array<{ id: string | number; patient?: string; rating?: number; comment?: string; status?: string; date?: string }>>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({ patient: "", rating: "5", comment: "", status: "published", date: "" });
  const { toast } = useToast();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "reviews"],
    queryFn: () => clinicApi.reviews(),
  });
  const reviews = useMemo<Array<{ id: string | number; patient?: string; rating?: number; comment?: string; status?: string; date?: string }>>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: Array<{ id: string | number; patient?: string; rating?: number; comment?: string; status?: string; date?: string }> })?.data ?? [];
  }, [data]);
  useEffect(() => setItems(reviews), [reviews]);
  const filtered = items.filter((r) => `${r.patient ?? ""} ${r.comment ?? ""}`.toLowerCase().includes(search.toLowerCase()));
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const statusStyles: Record<string, string> = {
    published: "bg-success/10 text-success",
    hidden: "bg-muted text-muted-foreground",
  };

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ patient: "", rating: "5", comment: "", status: "published", date: "" });
  };
  const openShow = (r: { id: string | number; patient?: string; rating?: number; comment?: string; status?: string; date?: string }) => {
    setDialogMode("show");
    setActiveId(String(r.id));
    setForm({ patient: r.patient ?? "", rating: String(r.rating ?? 0), comment: r.comment ?? "", status: r.status ?? "published", date: r.date ?? "" });
  };
  const openEdit = (r: { id: string | number; patient?: string; rating?: number; comment?: string; status?: string; date?: string }) => {
    setDialogMode("edit");
    setActiveId(String(r.id));
    setForm({ patient: r.patient ?? "", rating: String(r.rating ?? 0), comment: r.comment ?? "", status: r.status ?? "published", date: r.date ?? "" });
  };
  const onSave = () => {
    if (!form.patient.trim()) {
      toast({ title: "Patient is required", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      setItems((prev) => [{ id: `local-${Date.now()}`, patient: form.patient, rating: Number(form.rating || 0), comment: form.comment, status: form.status, date: form.date || new Date().toISOString().slice(0, 10) }, ...prev]);
      toast({ title: "Review added" });
    } else if (dialogMode === "edit") {
      setItems((prev) => prev.map((r) => (String(r.id) === activeId ? { ...r, patient: form.patient, rating: Number(form.rating || 0), comment: form.comment, status: form.status, date: form.date } : r)));
      toast({ title: "Review updated" });
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Reviews</h2>
          <p className="text-muted-foreground text-sm mt-1">Table with add, show, and edit dialogs</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2"><Plus className="h-4 w-4" />Add Review</Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search reviews..." value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} className="pl-10" />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Patient</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Rating</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Comment</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && <tr><td className="p-4 text-muted-foreground" colSpan={6}>Loading reviews...</td></tr>}
              {error && <tr><td className="p-4 text-destructive" colSpan={6}>{error instanceof Error ? error.message : "Failed to load reviews"}</td></tr>}
              {!isLoading && !error && paged.length === 0 && <tr><td className="p-4 text-muted-foreground" colSpan={6}>No reviews found.</td></tr>}
              {paged.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.patient ?? "—"}</td>
                  <td className="p-4"><div className="flex items-center gap-1">{Array.from({ length: 5 }).map((_, i) => <Star key={i} className={`h-3.5 w-3.5 ${i < (r.rating ?? 0) ? "text-warning fill-current" : "text-muted"}`} />)}</div></td>
                  <td className="p-4 text-muted-foreground max-w-[280px] truncate">{r.comment ?? ""}</td>
                  <td className="p-4"><Badge variant="secondary" className={statusStyles[r.status ?? ""] ?? ""}>{r.status ?? "—"}</Badge></td>
                  <td className="p-4"><div className="flex gap-2"><Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(r)}><Eye className="h-4 w-4" />Show</Button><Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(r)}><Edit className="h-4 w-4" />Edit</Button></div></td>
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
          <DialogHeader><DialogTitle>{dialogMode === "add" ? "Add Review" : dialogMode === "edit" ? "Edit Review" : "Review Details"}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2"><Label>Patient</Label><Input value={form.patient} onChange={(e) => setForm((f) => ({ ...f, patient: e.target.value }))} disabled={dialogMode === "show"} /></div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2"><Label>Rating</Label><Input type="number" min={1} max={5} value={form.rating} onChange={(e) => setForm((f) => ({ ...f, rating: e.target.value }))} disabled={dialogMode === "show"} /></div>
              <div className="space-y-2"><Label>Status</Label><Input value={form.status} onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))} disabled={dialogMode === "show"} /></div>
            </div>
            <div className="space-y-2"><Label>Comment</Label><Input value={form.comment} onChange={(e) => setForm((f) => ({ ...f, comment: e.target.value }))} disabled={dialogMode === "show"} /></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setDialogMode(null)}>Close</Button>{dialogMode !== "show" && <Button onClick={onSave}>Save</Button>}</DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}

