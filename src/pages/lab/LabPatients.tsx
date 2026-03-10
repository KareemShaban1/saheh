import { useEffect, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Search, ChevronLeft, ChevronRight, User, Link2Off, History } from "lucide-react";
import { BrowserMultiFormatReader } from "@zxing/browser";
import { Link } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { labApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";
import { useLanguage } from "@/contexts/LanguageContext";
type PatientRow = {
  id: string | number;
  patient_code?: string | number | null;
  name?: string;
  phone?: string | null;
  email?: string | null;
  age?: string | null;
  gender?: string | null;
  blood_type?: string | null;
  status?: string;
};

export default function LabPatients() {
  const { t } = useLanguage();
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const perPage = 10;

  const [addOpen, setAddOpen] = useState(false);
  const [assignOpen, setAssignOpen] = useState(false);

  const [addForm, setAddForm] = useState({
    name: "",
    phone: "",
    email: "",
    address: "",
    gender: "male" as "male" | "female",
  });
  const [assignForm, setAssignForm] = useState({
    patient_code: "",
    qr_value: "",
  });
  const [unassignId, setUnassignId] = useState<string | number | null>(null);
  const [isScanning, setIsScanning] = useState(false);
  const [scanMessage, setScanMessage] = useState("");
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const zxingReaderRef = useRef<BrowserMultiFormatReader | null>(null);
  const zxingControlsRef = useRef<{ stop: () => void } | null>(null);

  const { data, isLoading, error } = useQuery({
    queryKey: ["lab", "patients", page, perPage, search],
    queryFn: () =>
      labApi.patients({
        page: String(page),
        per_page: String(perPage),
        ...(search.trim() ? { search: search.trim() } : {}),
      }),
  });

  const root = (data as { data?: unknown })?.data ?? data;
  const patients = ((root as { data?: PatientRow[] })?.data ?? []) as PatientRow[];
  const pagination = (root as { pagination?: { current_page?: number; last_page?: number; total?: number } })?.pagination;

  const reloadPatients = async () => {
    await queryClient.invalidateQueries({ queryKey: ["lab", "patients"] });
  };

  const createPatientMutation = useMutation({
    mutationFn: () =>
      labApi.createPatient({
        name: addForm.name.trim(),
        phone: addForm.phone.trim(),
        email: addForm.email.trim() || undefined,
        address: addForm.address.trim() || undefined,
        gender: addForm.gender,
      }),
    onSuccess: async () => {
      await reloadPatients();
      toast({ title: "Patient created and assigned to your lab" });
      setAddOpen(false);
      setAddForm({ name: "", phone: "", email: "", address: "", gender: "male" });
    },
    onError: (e) => {
      toast({
        title: "Failed to create patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const assignPatientMutation = useMutation({
    mutationFn: () =>
      labApi.assignPatientByCode({
        patient_code: assignForm.patient_code.trim() || undefined,
        qr_value: assignForm.qr_value.trim() || undefined,
      }),
    onSuccess: async () => {
      await reloadPatients();
      toast({ title: "Patient assignment completed" });
      setAssignOpen(false);
      setAssignForm({ patient_code: "", qr_value: "" });
    },
    onError: (e) => {
      toast({
        title: "Failed to assign patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const unassignPatientMutation = useMutation({
    mutationFn: () => labApi.unassignPatient(unassignId as string | number),
    onSuccess: async () => {
      await reloadPatients();
      toast({ title: "Patient unassigned successfully" });
      setUnassignId(null);
    },
    onError: (e) => {
      toast({
        title: "Failed to unassign patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    },
  });

  const onSubmitAdd = () => {
    if (!addForm.name.trim() || !addForm.phone.trim()) {
      toast({ title: "Name and phone are required", variant: "destructive" });
      return;
    }
    createPatientMutation.mutate();
  };

  const onSubmitAssign = () => {
    if (!assignForm.patient_code.trim() && !assignForm.qr_value.trim()) {
      toast({ title: "Enter patient code or QR value", variant: "destructive" });
      return;
    }
    assignPatientMutation.mutate();
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
        {
          video: { facingMode: { ideal: "environment" } },
        },
        videoRef.current,
        (result) => {
          const raw = result?.getText()?.trim();
          if (!raw) return;

          const numeric = raw.match(/([0-9]{6,})/)?.[1] ?? "";
          setAssignForm({
            patient_code: numeric,
            qr_value: raw,
          });
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
        <h2 className="text-2xl font-bold"> {t("lab.patients.title")}</h2>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setAssignOpen(true)}>
            {t("lab.patients.assign_existing")}
          </Button>
          <Button className="gradient-primary text-primary-foreground border-0" onClick={() => setAddOpen(true)}>
            {t("lab.patients.add")}
          </Button>
        </div>
      </div>

      <div className="relative mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search by name or phone..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="pl-10 max-w-md"
        />
      </div>

      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-start font-medium p-4 text-muted-foreground">#</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.patient_code")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.name")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.phone")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.age")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.gender")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.blood")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.status")}</th>
                <th className="text-start font-medium p-4 text-muted-foreground">{t("lab.patients.actions")}</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr><td className="p-4 text-muted-foreground" colSpan={9}>Loading patients...</td></tr>
              )}
              {error && (
                <tr><td className="p-4 text-destructive" colSpan={9}>{error instanceof Error ? error.message : "Failed to load patients"}</td></tr>
              )}
              {!isLoading && !error && patients.length === 0 && (
                <tr><td className="p-4 text-muted-foreground" colSpan={9}>No patients found.</td></tr>
              )}
              {!isLoading && !error && patients.map((p) => (
                <tr key={String(p.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(p.id)}</td>
                  <td className="p-4 text-muted-foreground">{String(p.patient_code ?? "—")}</td>
                  <td className="p-4">
                    <div className="flex items-center gap-2">
                      <div className="h-8 w-8 rounded-full bg-sidebar-accent flex items-center justify-center">
                        <User className="h-4 w-4 text-primary" />
                      </div>
                      <div>
                        <p className="font-medium">{p.name ?? "—"}</p>
                        <p className="text-xs text-muted-foreground">{p.email ?? "—"}</p>
                      </div>
                    </div>
                  </td>
                  <td className="p-4 text-muted-foreground">{p.phone ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.age ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.gender ?? "—"}</td>
                  <td className="p-4"><Badge variant="outline">{p.blood_type || "—"}</Badge></td>
                  <td className="p-4">
                    <Badge
                      variant="secondary"
                      className={p.status === "active" ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}
                    >
                      {p.status ?? "active"}
                    </Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button asChild size="sm" variant="outline" className="gap-2">
                        <Link to={`/lab-dashboard/patients/${p.id}/history`}>
                          <History className="h-4 w-4" />
                          History
                        </Link>
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        className="gap-2 text-destructive"
                        onClick={() => setUnassignId(p.id)}
                      >
                        <Link2Off className="h-4 w-4" />
                        Unassign
                      </Button>
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
            <Button
              variant="outline"
              size="icon"
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              disabled={(pagination?.current_page ?? page) <= 1}
            >
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => setPage((p) => Math.min(pagination?.last_page ?? p, p + 1))}
              disabled={(pagination?.current_page ?? page) >= (pagination?.last_page ?? 1)}
            >
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      <Dialog open={addOpen} onOpenChange={setAddOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Add Patient</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1">
              <Label htmlFor="lab-patient-name">Name</Label>
              <Input
                id="lab-patient-name"
                value={addForm.name}
                onChange={(e) => setAddForm((f) => ({ ...f, name: e.target.value }))}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="lab-patient-phone">Phone</Label>
              <Input
                id="lab-patient-phone"
                value={addForm.phone}
                onChange={(e) => setAddForm((f) => ({ ...f, phone: e.target.value }))}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="lab-patient-email">Email (optional)</Label>
              <Input
                id="lab-patient-email"
                value={addForm.email}
                onChange={(e) => setAddForm((f) => ({ ...f, email: e.target.value }))}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="lab-patient-address">Address (optional)</Label>
              <Input
                id="lab-patient-address"
                value={addForm.address}
                onChange={(e) => setAddForm((f) => ({ ...f, address: e.target.value }))}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="lab-patient-gender">Gender</Label>
              <select
                id="lab-patient-gender"
                title="Patient gender"
                className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                value={addForm.gender}
                onChange={(e) => setAddForm((f) => ({ ...f, gender: e.target.value as "male" | "female" }))}
              >
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAddOpen(false)}>Cancel</Button>
            <Button onClick={onSubmitAdd} disabled={createPatientMutation.isPending}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Assign Existing Patient</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1">
              <Label htmlFor="assign-patient-code">Patient Code</Label>
              <Input
                id="assign-patient-code"
                placeholder="Example: 20260001"
                value={assignForm.patient_code}
                onChange={(e) => setAssignForm((f) => ({ ...f, patient_code: e.target.value }))}
              />
            </div>
            <div className="space-y-1">
              <Label htmlFor="assign-patient-qr">QR Value (scanned text)</Label>
              <Input
                id="assign-patient-qr"
                placeholder="Paste QR content or scan directly into this field"
                value={assignForm.qr_value}
                onChange={(e) => setAssignForm((f) => ({ ...f, qr_value: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label>Scan with Camera</Label>
              <video
                ref={videoRef}
                className="w-full max-h-64 rounded-md border bg-black/90"
                muted
                playsInline
              />
              <div className="flex gap-2">
                {!isScanning ? (
                  <Button type="button" variant="outline" onClick={() => void startScanner()}>
                    Start Camera Scan
                  </Button>
                ) : (
                  <Button type="button" variant="outline" onClick={stopScanner}>
                    Stop Camera
                  </Button>
                )}
                {scanMessage ? (
                  <p className="text-xs text-muted-foreground self-center">{scanMessage}</p>
                ) : null}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAssignOpen(false)}>Cancel</Button>
            <Button onClick={onSubmitAssign} disabled={assignPatientMutation.isPending}>Assign</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={Boolean(unassignId)} onOpenChange={() => setUnassignId(null)}>
        <DialogContent className="sm:max-w-sm">
          <DialogHeader>
            <DialogTitle>Unassign patient?</DialogTitle>
          </DialogHeader>
          <p className="text-sm text-muted-foreground">
            This patient will be removed from your lab patients list.
          </p>
          <DialogFooter>
            <Button variant="outline" onClick={() => setUnassignId(null)}>
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={() => unassignPatientMutation.mutate()}
              disabled={unassignPatientMutation.isPending}
            >
              {unassignPatientMutation.isPending ? "Unassigning..." : "Unassign"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
