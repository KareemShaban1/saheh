import { useEffect, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, QrCode, ChevronLeft, ChevronRight, User, Eye, Link2Off, History } from "lucide-react";
import { Link } from "react-router-dom";
import { BrowserMultiFormatReader } from "@zxing/browser";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { radiologyApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type Patient = {
  id: string | number;
  patient_code?: string;
  name: string;
  phone?: string | null;
  email?: string | null;
  address?: string | null;
  age?: string | null;
  gender?: "male" | "female" | null;
  blood_group?: string | null;
  status?: "active" | "inactive";
};

export default function RadiologyPatients() {
  const queryClient = useQueryClient();
  const [showOpen, setShowOpen] = useState(false);
  const [assignOpen, setAssignOpen] = useState(false);
  const [activeRow, setActiveRow] = useState<Patient | null>(null);
  const [unassignId, setUnassignId] = useState<string | number | null>(null);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [assignForm, setAssignForm] = useState({ patient_code: "", qr_value: "" });
  const [isScanning, setIsScanning] = useState(false);
  const [scanMessage, setScanMessage] = useState("");
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const zxingReaderRef = useRef<BrowserMultiFormatReader | null>(null);
  const zxingControlsRef = useRef<{ stop: () => void } | null>(null);
  const { toast } = useToast();
  const perPage = 10;

  const patientsQuery = useQuery({
    queryKey: ["radiology", "patients", page, perPage, search],
    queryFn: () =>
      radiologyApi.patients({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const root = (patientsQuery.data as { data?: unknown })?.data ?? patientsQuery.data;
  const rows = ((root as { data?: Patient[] })?.data ?? []) as Patient[];
  const pagination = (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;

  const assignMutation = useMutation({
    mutationFn: () =>
      radiologyApi.assignPatientByCode({
        patient_code: assignForm.patient_code.trim() || undefined,
        qr_value: assignForm.qr_value.trim() || undefined,
      }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "patients"] });
      toast({ title: "Patient assigned" });
      setAssignOpen(false);
      setAssignForm({ patient_code: "", qr_value: "" });
    },
    onError: (e) =>
      toast({
        title: "Failed to assign patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const unassignMutation = useMutation({
    mutationFn: () => radiologyApi.unassignPatient(unassignId as string | number),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["radiology", "patients"] });
      toast({ title: "Patient unassigned" });
      setUnassignId(null);
    },
    onError: (e) =>
      toast({
        title: "Failed to unassign patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openShow = (p: Patient) => {
    setActiveRow(p);
    setShowOpen(true);
  };

  const submitAssign = () => {
    if (!assignForm.patient_code.trim() && !assignForm.qr_value.trim()) {
      toast({ title: "Enter patient code or QR value", variant: "destructive" });
      return;
    }
    assignMutation.mutate();
  };

  const stopScanner = () => {
    if (zxingControlsRef.current) {
      zxingControlsRef.current.stop();
      zxingControlsRef.current = null;
    }
    if (zxingReaderRef.current) {
      zxingReaderRef.current = null;
    }
    if (videoRef.current) {
      const stream = videoRef.current.srcObject as MediaStream | null;
      if (stream) {
        stream.getTracks().forEach((track) => track.stop());
      }
      videoRef.current.srcObject = null;
    }
    setIsScanning(false);
  };

  const startScanner = async () => {
    try {
      stopScanner();
      if (!videoRef.current) return;

      const reader = new BrowserMultiFormatReader();
      zxingReaderRef.current = reader;

      const controls = await reader.decodeFromConstraints(
        { video: { facingMode: { ideal: "environment" } } },
        videoRef.current,
        (result) => {
          const raw = result?.getText()?.trim();
          if (!raw) return;

          const numeric = raw.match(/([0-9]{6,})/)?.[1] ?? "";
          setAssignForm((prev) => ({ ...prev, patient_code: numeric, qr_value: raw }));
          setScanMessage("Code detected. Ready to assign.");
          stopScanner();
        },
      );

      zxingControlsRef.current = controls as { stop: () => void };
      setIsScanning(true);
      setScanMessage("Point camera to patient QR code");
    } catch (e) {
      toast({
        title: "Cannot start camera scan",
        description: e instanceof Error ? e.message : "Please allow camera access and try again.",
        variant: "destructive",
      });
    }
  };

  useEffect(() => {
    if (!assignOpen) {
      stopScanner();
      setScanMessage("");
    }
  }, [assignOpen]);

  useEffect(() => {
    return () => {
      stopScanner();
    };
  }, []);

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-2xl font-bold">Radiology Patients</h2>
        <Button onClick={() => setAssignOpen(true)} size="sm" className="gradient-primary text-primary-foreground border-0">
          <QrCode className="h-4 w-4 mr-1" />
          Assign Patient
        </Button>
      </div>

      <div className="relative mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search by name or phone..."
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          className="pl-10 max-w-md"
        />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead><tr className="border-b bg-muted/50">
              <th className="text-left font-medium p-4 text-muted-foreground">#</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Name</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Code</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Phone</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Age</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Gender</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Status</th>
              <th className="text-left font-medium p-4 text-muted-foreground">Actions</th>
            </tr></thead>
            <tbody className="divide-y">
              {patientsQuery.isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>Loading patients...</td></tr>
              )}
              {patientsQuery.error && (
                <tr><td className="p-4 text-destructive" colSpan={8}>{patientsQuery.error instanceof Error ? patientsQuery.error.message : "Failed to load patients"}</td></tr>
              )}
              {!patientsQuery.isLoading && !patientsQuery.error && rows.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={8}>No assigned patients found.</td></tr>
              )}
              {!patientsQuery.isLoading && !patientsQuery.error && rows.map(p => (
                <tr key={p.id} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{p.id}</td>
                  <td className="p-4"><div className="flex items-center gap-2"><div className="h-8 w-8 rounded-full bg-sidebar-accent flex items-center justify-center"><User className="h-4 w-4 text-primary" /></div><div><p className="font-medium">{p.name}</p><p className="text-xs text-muted-foreground">{p.email ?? "—"}</p></div></div></td>
                  <td className="p-4 text-muted-foreground">{p.patient_code ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.phone ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.age ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.gender ?? "—"}</td>
                  <td className="p-4"><Badge variant="secondary" className={p.status === "active" ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}>{p.status ?? "active"}</Badge></td>
                  <td className="p-4">
                    <div className="flex gap-1">
                      <button title="Show patient" onClick={() => openShow(p)} className="p-1.5 rounded hover:bg-muted"><Eye className="h-4 w-4 text-muted-foreground" /></button>
                      <Link to={`/radiology-dashboard/patients/${p.id}/history`} className="p-1.5 rounded hover:bg-muted" title="Patient history">
                        <History className="h-4 w-4 text-muted-foreground" />
                      </Link>
                      <button title="Unassign patient" onClick={() => setUnassignId(p.id)} className="p-1.5 rounded hover:bg-muted"><Link2Off className="h-4 w-4 text-destructive" /></button>
                    </div>
                  </td>
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

      <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader><DialogTitle>Assign Existing Patient</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid gap-2">
              <Label>Patient Code</Label>
              <Input
                placeholder="e.g. 123456"
                value={assignForm.patient_code}
                onChange={(e) => setAssignForm((prev) => ({ ...prev, patient_code: e.target.value }))}
              />
            </div>
            <div className="grid gap-2">
              <Label>QR Scan Value</Label>
              <Textarea
                rows={3}
                placeholder="Paste QR result here (supports full QR text/url)"
                value={assignForm.qr_value}
                onChange={(e) => setAssignForm((prev) => ({ ...prev, qr_value: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>Scan with Camera</Label>
              <video ref={videoRef} className="w-full max-h-64 rounded-md border bg-black/90" muted playsInline />
              <div className="flex gap-2">
                {!isScanning ? (
                  <Button type="button" variant="outline" onClick={() => void startScanner()}>Start Camera Scan</Button>
                ) : (
                  <Button type="button" variant="outline" onClick={stopScanner}>Stop Camera</Button>
                )}
                {scanMessage ? <p className="text-xs text-muted-foreground self-center">{scanMessage}</p> : null}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAssignOpen(false)}>Cancel</Button>
            <Button onClick={submitAssign} disabled={assignMutation.isPending}>
              {assignMutation.isPending ? "Assigning..." : "Assign"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={showOpen} onOpenChange={setShowOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader><DialogTitle>Patient Details</DialogTitle></DialogHeader>
          <div className="grid gap-4 py-2">
            <div className="grid gap-2"><Label>Full Name</Label><Input value={activeRow?.name ?? ""} disabled /></div>
            <div className="grid grid-cols-2 gap-4">
              <div className="grid gap-2"><Label>Phone</Label><Input value={activeRow?.phone ?? ""} disabled /></div>
              <div className="grid gap-2"><Label>Email</Label><Input value={activeRow?.email ?? ""} disabled /></div>
            </div>
            <div className="grid grid-cols-3 gap-4">
              <div className="grid gap-2"><Label>Age</Label><Input value={activeRow?.age ?? ""} disabled /></div>
              <div className="grid gap-2"><Label>Gender</Label><Input value={activeRow?.gender ?? ""} disabled /></div>
              <div className="grid gap-2"><Label>Code</Label><Input value={activeRow?.patient_code ?? ""} disabled /></div>
            </div>
            <div className="grid gap-2"><Label>Address</Label><Textarea value={activeRow?.address ?? ""} rows={2} disabled /></div>
            <div className="grid gap-2"><Label>Blood Group</Label><Input value={activeRow?.blood_group ?? ""} disabled /></div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(unassignId)} onOpenChange={() => setUnassignId(null)}>
        <DialogContent className="max-w-sm">
          <DialogHeader><DialogTitle>Unassign Patient?</DialogTitle></DialogHeader>
          <p className="text-sm text-muted-foreground">This patient will be removed from this radiology center patient list.</p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setUnassignId(null)}>Cancel</Button>
            <Button variant="destructive" onClick={() => unassignMutation.mutate()} disabled={unassignMutation.isPending}>
              {unassignMutation.isPending ? "Unassigning..." : "Unassign"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
