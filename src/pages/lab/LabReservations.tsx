import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { labApi } from "@/lib/api";

type ReservationRow = {
  id: string | number;
  patient?: string;
  test?: string;
  date?: string;
  time?: string;
  status?: string;
  payment?: string;
};

const statusStyles: Record<string, string> = {
  confirmed: "bg-success/10 text-success",
  pending: "bg-warning/10 text-warning",
  completed: "bg-muted text-muted-foreground",
  cancelled: "bg-destructive/10 text-destructive",
};
const paymentStyles: Record<string, string> = { paid: "bg-success/10 text-success", unpaid: "bg-destructive/10 text-destructive", partial: "bg-warning/10 text-warning" };

export default function LabReservations() {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [page, setPage] = useState(1);
  const perPage = 10;

  const { data, isLoading, error } = useQuery({
    queryKey: ["lab", "reservations", page, perPage, search],
    queryFn: () =>
      labApi.reservations({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const root = (data as { data?: unknown })?.data ?? data;
  const rows = ((root as { data?: ReservationRow[] })?.data ?? []) as ReservationRow[];
  const pagination = (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;
  const filtered = rows.filter((r) => statusFilter === "all" || String(r.status ?? "pending") === statusFilter);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">Lab Reservations</h2>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-4">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input placeholder="Search patient or test..." value={search} onChange={e => setSearch(e.target.value)} className="pl-10" />
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
              <th className="text-left font-medium p-4 text-muted-foreground">Test</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Date</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Time</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Status</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Payment</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Actions</th>
            </tr></thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>Loading reservations...</td></tr>
              )}
              {error && (
                <tr><td className="p-4 text-destructive" colSpan={8}>{error instanceof Error ? error.message : "Failed to load reservations"}</td></tr>
              )}
              {!isLoading && !error && filtered.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>No reservations found.</td></tr>
              )}
              {!isLoading && !error && filtered.map((r) => (
                <tr key={String(r.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(r.id)}</td>
                  <td className="p-4 font-medium">{r.patient ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.test ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.date ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{r.time ?? "—"}</td>
                  <td className="p-4"><Badge variant="secondary" className={statusStyles[String(r.status ?? "pending")] ?? ""}>{String(r.status ?? "pending")}</Badge></td>
                  <td className="p-4"><Badge variant="secondary" className={paymentStyles[String(r.payment ?? "unpaid")] ?? ""}>{String(r.payment ?? "unpaid")}</Badge></td>
                  <td className="p-4 text-muted-foreground">—</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="flex items-center justify-between p-4 border-t">
          <p className="text-sm text-muted-foreground">
            Page {pagination?.current_page ?? page} of {pagination?.last_page ?? 1}
            {typeof pagination?.total === "number" ? ` (${pagination.total} total)` : ""}
          </p>
          <div className="flex gap-1">
            <Button variant="outline" size="icon" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={(pagination?.current_page ?? page) <= 1}><ChevronLeft className="h-4 w-4" /></Button>
            <Button variant="outline" size="icon" onClick={() => setPage((p) => Math.min(pagination?.last_page ?? p, p + 1))} disabled={(pagination?.current_page ?? page) >= (pagination?.last_page ?? 1)}><ChevronRight className="h-4 w-4" /></Button>
          </div>
        </div>
      </div>
    </div>
  );
}
