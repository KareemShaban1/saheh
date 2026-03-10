import { useState } from "react";
import { Search, Plus, Edit, Trash2, Eye, ChevronLeft, ChevronRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";

interface CrudTableProps {
  title: string;
  columns: { key: string; label: string }[];
  data: Record<string, string>[];
  onAdd?: () => void;
}

export default function CrudTable({ title, columns, data, onAdd }: CrudTableProps) {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const perPage = 10;

  const filtered = data.filter(row =>
    Object.values(row).some(v => v.toLowerCase().includes(search.toLowerCase()))
  );
  const paged = filtered.slice((page - 1) * perPage, page * perPage);
  const totalPages = Math.ceil(filtered.length / perPage);

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h2 className="text-2xl font-bold">{title}</h2>
        {onAdd ? (
          <Button size="sm" className="gap-2 gradient-primary text-primary-foreground border-0" onClick={onAdd}>
            <Plus className="h-4 w-4" /> Add New
          </Button>
        ) : null}
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                {columns.map(col => (
                  <th key={col.key} className="text-start font-medium p-4 text-muted-foreground">{col.label}</th>
                ))}
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {paged.map((row, i) => (
                <tr key={i} className="hover:bg-muted/30 transition-colors">
                  {columns.map(col => (
                    <td key={col.key} className="p-4">
                      {col.key === "status" ? (
                        <Badge variant="secondary" className={row[col.key] === "active" ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}>
                          {row[col.key]}
                        </Badge>
                      ) : (
                        <span className={col.key === "id" ? "text-muted-foreground" : ""}>{row[col.key]}</span>
                      )}
                    </td>
                  ))}
                  <td className="p-4">
                    <div className="flex gap-1">
                      <button className="p-1.5 rounded hover:bg-muted" aria-label="View"><Eye className="h-4 w-4 text-muted-foreground" /></button>
                      <button className="p-1.5 rounded hover:bg-muted" aria-label="Edit"><Edit className="h-4 w-4 text-muted-foreground" /></button>
                      <button className="p-1.5 rounded hover:bg-muted" aria-label="Delete"><Trash2 className="h-4 w-4 text-destructive" /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {totalPages > 1 && (
          <div className="flex items-center justify-between p-4 border-t">
            <p className="text-sm text-muted-foreground">Page {page} of {totalPages}</p>
            <div className="flex gap-1">
              <Button variant="outline" size="icon" onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}>
                <ChevronLeft className="h-4 w-4" />
              </Button>
              <Button variant="outline" size="icon" onClick={() => setPage(p => Math.min(totalPages, p + 1))} disabled={page === totalPages}>
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
