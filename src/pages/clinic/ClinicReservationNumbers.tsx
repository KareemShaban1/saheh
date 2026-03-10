import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Edit, Eye, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type NumberRow = {
  id: string | number;
  doctor_id?: string | number;
  doctor_name?: string;
  reservation_date?: string;
  num_of_reservations?: number;
};

export default function ClinicReservationNumbers() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [items, setItems] = useState<NumberRow[]>([]);
  const [dialogMode, setDialogMode] = useState<"add" | "edit" | "show" | null>(null);
  const [activeId, setActiveId] = useState<string>("");
  const [form, setForm] = useState({
    doctor_id: "",
    reservation_date: "",
    num_of_reservations: "0",
  });
  const { toast } = useToast();
  const perPage = 10;
  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "reservation-numbers"],
    queryFn: () => clinicApi.reservationNumbers({ per_page: "500" }),
  });
  const rows = useMemo<NumberRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as { data?: NumberRow[] })?.data ?? [];
  }, [data]);
  const doctorsQuery = useQuery({
    queryKey: ["clinic", "doctors", "for-numbers"],
    queryFn: () => clinicApi.doctors(),
  });
  const doctors = useMemo<Array<{ id: string | number; name?: string }>>(() => {
    const root = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
    return Array.isArray(root) ? (root as Array<{ id: string | number; name?: string }>) : [];
  }, [doctorsQuery.data]);
  const doctorNameById = (doctorId: string) =>
    doctors.find((d) => String(d.id) === doctorId)?.name ?? `Doctor ${doctorId}`;
  const doctorIdByName = (doctorName: string) =>
    String(doctors.find((d) => (d.name ?? "").toLowerCase() === doctorName.toLowerCase())?.id ?? "");

  useEffect(() => {
    setItems(rows);
  }, [rows]);

  const filtered = items.filter((r) =>
    `${r.doctor_name ?? ""} ${r.reservation_date ?? ""}`.toLowerCase().includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const openAdd = () => {
    setDialogMode("add");
    setActiveId("");
    setForm({ doctor_id: "", reservation_date: "", num_of_reservations: "0" });
  };

  const openShow = (row: NumberRow) => {
    setDialogMode("show");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : doctorIdByName(row.doctor_name ?? ""),
      reservation_date: row.reservation_date ?? "",
      num_of_reservations: String(row.num_of_reservations ?? 0),
    });
  };

  const openEdit = (row: NumberRow) => {
    setDialogMode("edit");
    setActiveId(String(row.id));
    setForm({
      doctor_id: row.doctor_id ? String(row.doctor_id) : doctorIdByName(row.doctor_name ?? ""),
      reservation_date: row.reservation_date ?? "",
      num_of_reservations: String(row.num_of_reservations ?? 0),
    });
  };

  const onSave = () => {
    if (!form.doctor_id || !form.reservation_date) {
      toast({ title: "Missing required fields", description: "Doctor and date are required.", variant: "destructive" });
      return;
    }
    if (dialogMode === "add") {
      setItems((prev) => [
        {
          id: `local-${Date.now()}`,
          doctor_id: form.doctor_id,
          doctor_name: doctorNameById(form.doctor_id),
          reservation_date: form.reservation_date,
          num_of_reservations: Number(form.num_of_reservations || 0),
        },
        ...prev,
      ]);
      toast({ title: "Added", description: "Reservation number row added in dashboard view." });
    } else if (dialogMode === "edit") {
      setItems((prev) =>
        prev.map((r) =>
          String(r.id) === activeId
            ? {
                ...r,
                doctor_id: form.doctor_id,
                doctor_name: doctorNameById(form.doctor_id),
                reservation_date: form.reservation_date,
                num_of_reservations: Number(form.num_of_reservations || 0),
              }
            : r,
        ),
      );
      toast({ title: "Updated", description: "Reservation number row updated in dashboard view." });
    }
    setDialogMode(null);
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">Reservation Numbers</h2>
          <p className="text-muted-foreground text-sm mt-1">Table with add, show, and edit dialogs</p>
        </div>
        <Button onClick={openAdd} className="gradient-primary text-primary-foreground border-0 gap-2">
          <Plus className="h-4 w-4" />
          Add
        </Button>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search by doctor or day..."
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-10"
        />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Doctor</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Date</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Reservations Count</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={5}>Loading reservation numbers...</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={5}>
                    {error instanceof Error ? error.message : "Failed to load reservation numbers"}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={5}>No data found.</td>
                </tr>
              )}
              {paged.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.doctor_name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.reservation_date ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.num_of_reservations ?? 0}</td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openShow(r)}>
                        <Eye className="h-4 w-4" />
                        Show
                      </Button>
                      <Button variant="outline" size="sm" className="gap-2" onClick={() => openEdit(r)}>
                        <Edit className="h-4 w-4" />
                        Edit
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {!isLoading && !error && totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">Page {safePage} of {totalPages}</p>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={safePage <= 1}>
                Previous
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
                disabled={safePage >= totalPages}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </div>

      <Dialog open={dialogMode !== null} onOpenChange={(open) => !open && setDialogMode(null)}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>
              {dialogMode === "add" ? "Add Reservation Number" : dialogMode === "edit" ? "Edit Reservation Number" : "Reservation Number Details"}
            </DialogTitle>
          </DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="space-y-2">
              <Label>Doctor</Label>
              <Select
                value={form.doctor_id}
                onValueChange={(v) => setForm((prev) => ({ ...prev, doctor_id: v }))}
                disabled={dialogMode === "show"}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select doctor" />
                </SelectTrigger>
                <SelectContent>
                  {doctors.map((d) => (
                    <SelectItem key={String(d.id)} value={String(d.id)}>
                      {d.name ?? `Doctor ${d.id}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="grid sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Date</Label>
                <Input
                  type="date"
                  value={form.reservation_date}
                  onChange={(e) => setForm((prev) => ({ ...prev, reservation_date: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
              <div className="space-y-2">
                <Label>Reservations Count</Label>
                <Input
                  type="number"
                  value={form.num_of_reservations}
                  onChange={(e) => setForm((prev) => ({ ...prev, num_of_reservations: e.target.value }))}
                  disabled={dialogMode === "show"}
                />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogMode(null)}>Close</Button>
            {dialogMode !== "show" && <Button onClick={onSave}>Save</Button>}
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
