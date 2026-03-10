import { useState } from "react";
import { Search, Plus, Edit, Trash2, ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { useToast } from "@/hooks/use-toast";

interface Reservation { id: string; patient: string; scan: string; date: string; time: string; status: string; payment: string; }

const initial: Reservation[] = Array.from({ length: 15 }, (_, i) => ({
  id: String(i + 1),
  patient: ["Mohamed A.", "Sara K.", "Ali M.", "Fatma N.", "Youssef H."][i % 5],
  scan: ["MRI Brain", "CT Chest", "X-Ray Spine", "Ultrasound", "MRI Knee", "CT Abdomen"][i % 6],
  date: `Feb ${20 + (i % 10)}, 2026`,
  time: `${9 + (i % 6)}:${i % 2 === 0 ? "00" : "30"} ${9 + (i % 6) < 12 ? "AM" : "PM"}`,
  status: ["confirmed", "pending", "completed", "cancelled"][i % 4],
  payment: ["paid", "unpaid", "partial"][i % 3],
}));

const empty = { patient: "", scan: "", date: "", time: "", status: "pending", payment: "unpaid" };
const statusStyles: Record<string, string> = { confirmed: "bg-success/10 text-success", pending: "bg-warning/10 text-warning", completed: "bg-muted text-muted-foreground", cancelled: "bg-destructive/10 text-destructive" };
const paymentStyles: Record<string, string> = { paid: "bg-success/10 text-success", unpaid: "bg-destructive/10 text-destructive", partial: "bg-warning/10 text-warning" };

export default function RadiologyReservations() {
  const [data, setData] = useState(initial);
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Reservation | null>(null);
  const [form, setForm] = useState(empty);
  const [deleteConfirm, setDeleteConfirm] = useState<string | null>(null);
  const { toast } = useToast();
  const perPage = 10;

  const filtered = data.filter(r => {
    const ms = r.patient.toLowerCase().includes(search.toLowerCase()) || r.scan.toLowerCase().includes(search.toLowerCase());
    return ms && (statusFilter === "all" || r.status === statusFilter);
  });
  const paged = filtered.slice((page - 1) * perPage, page * perPage);
  const totalPages = Math.ceil(filtered.length / perPage);

  const openCreate = () => { setEditing(null); setForm(empty); setModalOpen(true); };
  const openEdit = (r: Reservation) => { setEditing(r); setForm({ patient: r.patient, scan: r.scan, date: r.date, time: r.time, status: r.status, payment: r.payment }); setModalOpen(true); };

  const save = () => {
    if (!form.patient || !form.scan) { toast({ title: "Error", description: "Patient & scan required", variant: "destructive" }); return; }
    if (editing) {
      setData(d => d.map(r => r.id === editing.id ? { ...r, ...form } : r));
      toast({ title: "Updated" });
    } else {
      setData(d => [...d, { ...form, id: String(Date.now()) }]);
      toast({ title: "Created" });
    }
    setModalOpen(false);
  };

  const remove = (id: string) => { setData(d => d.filter(r => r.id !== id)); setDeleteConfirm(null); toast({ title: "Deleted" }); };

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">Radiology Reservations</h2>
        <Button onClick={openCreate} size="sm" className="gradient-primary text-primary-foreground border-0"><Plus className="h-4 w-4 mr-1" />Add Reservation</Button>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Search patient or scan..." value={search} onChange={e => setSearch(e.target.value)} className="pl-10" />
        </div>
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-[160px]"><SelectValue placeholder="Status" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="confirmed">Confirmed</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="completed">Completed</SelectItem>
            <SelectItem value="cancelled">Cancelled</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead><tr className="border-b bg-muted/50">
              <th className="text-left font-medium p-4 text-muted-foreground">#</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Patient</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Scan</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Date</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Time</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Status</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Payment</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Actions</th>
            </tr></thead>
            <tbody className="divide-y">
              {paged.map(r => (
                <tr key={r.id} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{r.id}</td>
                  <td className="p-4 font-medium">{r.patient}</td>
                  <td className="p-4 text-muted-foreground">{r.scan}</td>
                  <td className="p-4 text-muted-foreground">{r.date}</td>
                  <td className="p-4 text-muted-foreground">{r.time}</td>
                  <td className="p-4"><Badge variant="secondary" className={statusStyles[r.status]}>{r.status}</Badge></td>
                  <td className="p-4"><Badge variant="secondary" className={paymentStyles[r.payment]}>{r.payment}</Badge></td>
                  <td className="p-4">
                    <div className="flex gap-1">
                      <button onClick={() => openEdit(r)} className="p-1.5 rounded hover:bg-muted"><Edit className="h-4 w-4 text-muted-foreground" /></button>
                      <button onClick={() => setDeleteConfirm(r.id)} className="p-1.5 rounded hover:bg-muted"><Trash2 className="h-4 w-4 text-destructive" /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="flex items-center justify-between p-4 border-t">
          <p className="text-sm text-muted-foreground">Showing {(page-1)*perPage+1}–{Math.min(page*perPage, filtered.length)} of {filtered.length}</p>
          <div className="flex gap-1">
            <Button variant="outline" size="icon" onClick={() => setPage(p => Math.max(1, p-1))} disabled={page===1}><ChevronLeft className="h-4 w-4" /></Button>
            <Button variant="outline" size="icon" onClick={() => setPage(p => Math.min(totalPages, p+1))} disabled={page===totalPages}><ChevronRight className="h-4 w-4" /></Button>
          </div>
        </div>
      </div>

      <Dialog open={modalOpen} onOpenChange={setModalOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader><DialogTitle>{editing ? "Edit Reservation" : "New Reservation"}</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid gap-2"><Label>Patient</Label><Input value={form.patient} onChange={e => setForm({...form, patient: e.target.value})} /></div>
            <div className="grid gap-2"><Label>Scan Type</Label><Input value={form.scan} onChange={e => setForm({...form, scan: e.target.value})} /></div>
            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2"><Label>Date</Label><Input type="date" value={form.date} onChange={e => setForm({...form, date: e.target.value})} /></div>
              <div className="grid gap-2"><Label>Time</Label><Input type="time" value={form.time} onChange={e => setForm({...form, time: e.target.value})} /></div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2"><Label>Status</Label>
                <Select value={form.status} onValueChange={v => setForm({...form, status: v})}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>
                  <SelectItem value="pending">Pending</SelectItem><SelectItem value="confirmed">Confirmed</SelectItem><SelectItem value="completed">Completed</SelectItem><SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent></Select>
              </div>
              <div className="grid gap-2"><Label>Payment</Label>
                <Select value={form.payment} onValueChange={v => setForm({...form, payment: v})}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>
                  <SelectItem value="unpaid">Unpaid</SelectItem><SelectItem value="paid">Paid</SelectItem><SelectItem value="partial">Partial</SelectItem>
                </SelectContent></Select>
              </div>
            </div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setModalOpen(false)}>Cancel</Button><Button onClick={save}>{editing ? "Update" : "Create"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={!!deleteConfirm} onOpenChange={() => setDeleteConfirm(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader><DialogTitle>Delete Reservation?</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">This action cannot be undone.</p>
          <DialogFooter><Button variant="outline" onClick={() => setDeleteConfirm(null)}>Cancel</Button><Button variant="destructive" onClick={() => deleteConfirm && remove(deleteConfirm)}>Delete</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
