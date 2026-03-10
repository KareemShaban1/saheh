import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { adminApi } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useToast } from "@/hooks/use-toast";

type ReviewRow = {
  id: string | number;
  patient: string;
  patient_id?: number;
  rating: number;
  comment: string;
  is_active: boolean;
};

export default function AdminReviews() {
  const { toast } = useToast();
  const queryClient = useQueryClient();
  const [detailsOpen, setDetailsOpen] = useState(false);
  const [detailsLoading, setDetailsLoading] = useState(false);
  const [detailsData, setDetailsData] = useState<Record<string, unknown> | null>(null);
  const { data, isLoading, error } = useQuery({
    queryKey: ["admin", "reviews"],
    queryFn: () => adminApi.reviews({ per_page: "100" }),
  });

  const rows = useMemo<ReviewRow[]>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return ((root as { data?: Array<Record<string, unknown>> })?.data ?? []).map((review) => ({
      id: String(review.id ?? "—"),
      patient: String(review.patient_name ?? "—"),
      patient_id: Number(review.patient_id ?? 0) || undefined,
      rating: Number(review.rating ?? 0),
      comment: String(review.comment ?? ""),
      is_active: Boolean(review.is_active ?? true),
    }));
  }, [data]);

  const createMutation = useMutation({
    mutationFn: (payload: { patient_id: number; rating: number; comment: string }) => adminApi.createReview(payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "reviews"] });
      toast({ title: "Review created" });
    },
  });
  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string | number; payload: { rating: number; comment: string } }) => adminApi.updateReview(id, payload),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "reviews"] });
      toast({ title: "Review updated" });
    },
  });
  const statusMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: "active" | "inactive" }) => adminApi.updateReviewStatus(id, status),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "reviews"] });
      toast({ title: "Review status updated" });
    },
  });
  const deleteMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteReview(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin", "reviews"] });
      toast({ title: "Review deleted" });
    },
  });

  const onAdd = () => {
    const patientIdRaw = window.prompt("Patient ID:");
    if (!patientIdRaw) return;
    const patient_id = Number(patientIdRaw);
    if (!Number.isFinite(patient_id) || patient_id <= 0) return;
    const ratingRaw = window.prompt("Rating (1-5):", "5") ?? "5";
    const rating = Number(ratingRaw);
    if (!Number.isFinite(rating) || rating < 1 || rating > 5) return;
    const comment = window.prompt("Comment:", "") ?? "";
    if (!comment.trim()) return;
    createMutation.mutate({ patient_id, rating, comment });
  };

  const onShow = async (id: string | number) => {
    try {
      setDetailsLoading(true);
      setDetailsOpen(true);
      const res = await adminApi.review(id);
      const root = (res as { data?: unknown })?.data ?? {};
      const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
      setDetailsData(row);
    } catch (e) {
      toast({
        title: "Failed to load review details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
      setDetailsOpen(false);
    } finally {
      setDetailsLoading(false);
    }
  };

  const onEdit = (row: ReviewRow) => {
    const ratingRaw = window.prompt("Edit rating (1-5):", String(row.rating || 5)) ?? String(row.rating || 5);
    const rating = Number(ratingRaw);
    if (!Number.isFinite(rating) || rating < 1 || rating > 5) return;
    const comment = window.prompt("Edit comment:", row.comment) ?? row.comment;
    updateMutation.mutate({ id: row.id, payload: { rating, comment } });
  };

  if (isLoading) return <div className="text-sm text-muted-foreground">Loading reviews...</div>;
  if (error) return <div className="text-sm text-destructive">{error instanceof Error ? error.message : "Failed to load reviews"}</div>;

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <h2 className="text-2xl font-bold">Reviews</h2>
        <Button onClick={onAdd} className="gradient-primary text-primary-foreground border-0">Add Review</Button>
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-left font-medium p-4 text-muted-foreground">#</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Patient</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Rating</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Comment</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {rows.map((row) => (
                <tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(row.id)}</td>
                  <td className="p-4">{row.patient}</td>
                  <td className="p-4">{row.rating || "—"}</td>
                  <td className="p-4">{row.comment || "—"}</td>
                  <td className="p-4">
                    <Badge variant="secondary" className={row.is_active ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}>
                      {row.is_active ? "active" : "inactive"}
                    </Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      <Button variant="outline" size="sm" onClick={() => onShow(row.id)}>Show</Button>
                      <Button variant="outline" size="sm" onClick={() => onEdit(row)}>Edit</Button>
                      <Button variant="outline" size="sm" onClick={() => statusMutation.mutate({ id: row.id, status: row.is_active ? "inactive" : "active" })}>
                        {row.is_active ? "Deactivate" : "Activate"}
                      </Button>
                      <Button variant="destructive" size="sm" onClick={() => window.confirm("Delete review?") && deleteMutation.mutate(row.id)}>
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <Dialog open={detailsOpen} onOpenChange={setDetailsOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Review Details</DialogTitle>
          </DialogHeader>
          {detailsLoading ? (
            <div className="py-3 text-sm text-muted-foreground">Loading...</div>
          ) : (
            <div className="space-y-2 text-sm">
              <p><span className="font-medium">Patient:</span> {String(detailsData?.patient_name ?? "—")}</p>
              <p><span className="font-medium">Rating:</span> {String(detailsData?.rating ?? "—")}</p>
              <p><span className="font-medium">Comment:</span> {String(detailsData?.comment ?? "—")}</p>
              <p><span className="font-medium">Status:</span> {Boolean(detailsData?.is_active ?? true) ? "active" : "inactive"}</p>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setDetailsOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
