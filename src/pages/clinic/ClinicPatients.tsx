import { useEffect, useMemo, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { BrowserMultiFormatReader } from "@zxing/browser";
import { Edit, Glasses, Plus, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type GlassesDistanceForm = {
  reservation_id: string;
  SPH_R_D: string;
  CYL_R_D: string;
  AX_R_D: string;
  SPH_L_D: string;
  CYL_L_D: string;
  AX_L_D: string;
  SPH_R_N: string;
  CYL_R_N: string;
  AX_R_N: string;
  SPH_L_N: string;
  CYL_L_N: string;
  AX_L_N: string;
};

const emptyGlassesForm = (): GlassesDistanceForm => ({
  reservation_id: "",
  SPH_R_D: "",
  CYL_R_D: "",
  AX_R_D: "",
  SPH_L_D: "",
  CYL_L_D: "",
  AX_L_D: "",
  SPH_R_N: "",
  CYL_R_N: "",
  AX_R_N: "",
  SPH_L_N: "",
  CYL_L_N: "",
  AX_L_N: "",
});

export default function ClinicPatients() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const perPage = 10;
  const [addOpen, setAddOpen] = useState(false);
  const [assignOpen, setAssignOpen] = useState(false);
  const [addForm, setAddForm] = useState({
    name: "",
    address: "",
    phone: "",
    email: "",
    gender: "male" as "male" | "female",
    doctor_ids: [] as string[],
  });
  const [assignForm, setAssignForm] = useState({
    patient_code: "",
    qr_value: "",
    doctor_ids: [] as string[],
  });
  const [isScanning, setIsScanning] = useState(false);
  const [scanMessage, setScanMessage] = useState("");
  const videoRef = useRef<HTMLVideoElement | null>(null);
  const zxingReaderRef = useRef<BrowserMultiFormatReader | null>(null);
  const zxingControlsRef = useRef<{ stop: () => void } | null>(null);
  const [glassesOpen, setGlassesOpen] = useState(false);
  const [glassesLoading, setGlassesLoading] = useState(false);
  const [activePatient, setActivePatient] = useState<{ id: number | string; name?: string } | null>(null);
  const [glassesForm, setGlassesForm] = useState<GlassesDistanceForm>(emptyGlassesForm);
  const [glassesHistory, setGlassesHistory] = useState<Array<{ id: number | string; created_at?: string | null; reservation_id?: number | null } & Omit<GlassesDistanceForm, "reservation_id">>>([]);

  const { data, isLoading, error } = useQuery({
    queryKey: ["clinic", "patients", "all"],
    queryFn: () => clinicApi.patients({ per_page: "500" }),
  });
  const doctorsQuery = useQuery({
    queryKey: ["clinic", "doctors", "for-patient-list"],
    queryFn: () => clinicApi.doctors(),
  });

  const doctors = useMemo<Array<{ id: string | number; name?: string }>>(() => {
    const root = (doctorsQuery.data as { data?: unknown })?.data ?? doctorsQuery.data;
    return Array.isArray(root) ? (root as Array<{ id: string | number; name?: string }>) : [];
  }, [doctorsQuery.data]);

  const patients = useMemo<Array<{
    id: number | string;
    name?: string;
    phone?: string;
    email?: string;
    assigned_doctor_names?: string[];
  }>>(() => {
    const root = (data as { data?: unknown })?.data ?? data;
    return (root as {
      data?: Array<{ id: number | string; name?: string; phone?: string; email?: string; assigned_doctor_names?: string[] }>;
    })?.data ?? [];
  }, [data]);
  const filtered = patients.filter((p) =>
    `${p.name ?? ""} ${p.phone ?? ""} ${(p.assigned_doctor_names ?? []).join(" ")}`
      .toLowerCase()
      .includes(search.toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(page, totalPages);
  const paged = filtered.slice((safePage - 1) * perPage, safePage * perPage);

  const reloadPatients = async () => {
    await queryClient.invalidateQueries({ queryKey: ["clinic", "patients"] });
  };

  const createPatientMutation = useMutation({
    mutationFn: () =>
      clinicApi.createPatient({
        doctor_ids: addForm.doctor_ids.map((id) => Number(id)),
        name: addForm.name.trim(),
        address: addForm.address.trim(),
        phone: addForm.phone.trim(),
        email: addForm.email.trim() || undefined,
        gender: addForm.gender,
      }),
    onSuccess: async () => {
      await reloadPatients();
      toast({ title: "Patient created and assigned to clinic" });
      setAddOpen(false);
      setAddForm({
        name: "",
        address: "",
        phone: "",
        email: "",
        gender: "male",
        doctor_ids: [],
      });
    },
    onError: (e) =>
      toast({
        title: "Failed to create patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const assignPatientMutation = useMutation({
    mutationFn: () =>
      clinicApi.assignPatientByCode({
        patient_code: assignForm.patient_code.trim() || undefined,
        qr_value: assignForm.qr_value.trim() || undefined,
        doctor_ids: assignForm.doctor_ids.map((id) => Number(id)),
      }),
    onSuccess: async () => {
      await reloadPatients();
      toast({ title: "Existing patient assigned successfully" });
      setAssignOpen(false);
      setAssignForm({ patient_code: "", qr_value: "", doctor_ids: [] });
    },
    onError: (e) =>
      toast({
        title: "Failed to assign patient",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const onSubmitAdd = () => {
    if (!addForm.name.trim() || !addForm.address.trim() || !addForm.phone.trim()) {
      toast({
        title: "Missing required fields",
        description: "Name, address and phone are required.",
        variant: "destructive",
      });
      return;
    }
    if (addForm.doctor_ids.length === 0) {
      toast({ title: "Select at least one doctor", variant: "destructive" });
      return;
    }
    createPatientMutation.mutate();
  };

  const onSubmitAssign = () => {
    if (!assignForm.patient_code.trim() && !assignForm.qr_value.trim()) {
      toast({ title: "Enter patient code or QR value", variant: "destructive" });
      return;
    }
    if (assignForm.doctor_ids.length === 0) {
      toast({ title: "Select at least one doctor", variant: "destructive" });
      return;
    }
    assignPatientMutation.mutate();
  };

  const savePatientGlassesMutation = useMutation({
    mutationFn: () => {
      if (!activePatient) return;
      const payload = {
        reservation_id: glassesForm.reservation_id.trim() ? Number(glassesForm.reservation_id) : undefined,
        SPH_R_D: glassesForm.SPH_R_D.trim() || undefined,
        CYL_R_D: glassesForm.CYL_R_D.trim() || undefined,
        AX_R_D: glassesForm.AX_R_D.trim() || undefined,
        SPH_L_D: glassesForm.SPH_L_D.trim() || undefined,
        CYL_L_D: glassesForm.CYL_L_D.trim() || undefined,
        AX_L_D: glassesForm.AX_L_D.trim() || undefined,
        SPH_R_N: glassesForm.SPH_R_N.trim() || undefined,
        CYL_R_N: glassesForm.CYL_R_N.trim() || undefined,
        AX_R_N: glassesForm.AX_R_N.trim() || undefined,
        SPH_L_N: glassesForm.SPH_L_N.trim() || undefined,
        CYL_L_N: glassesForm.CYL_L_N.trim() || undefined,
        AX_L_N: glassesForm.AX_L_N.trim() || undefined,
      };
      return clinicApi.createPatientGlassesDistance(activePatient.id, payload);
    },
    onSuccess: async () => {
      if (!activePatient) return;
      toast({ title: "Glasses distance saved" });
      const res = await clinicApi.patientGlassesDistances(activePatient.id);
      const root = (res as { data?: unknown })?.data ?? res;
      setGlassesHistory(Array.isArray(root) ? (root as Array<{ id: number | string; created_at?: string | null; reservation_id?: number | null } & Omit<GlassesDistanceForm, "reservation_id">>) : []);
      setGlassesForm(emptyGlassesForm());
    },
    onError: (e) =>
      toast({
        title: "Failed to save glasses distance",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const openPatientGlassesModal = async (patient: { id: number | string; name?: string }) => {
    setActivePatient(patient);
    setGlassesOpen(true);
    setGlassesLoading(true);
    setGlassesForm(emptyGlassesForm());
    try {
      const res = await clinicApi.patientGlassesDistances(patient.id);
      const root = (res as { data?: unknown })?.data ?? res;
      setGlassesHistory(Array.isArray(root) ? (root as Array<{ id: number | string; created_at?: string | null; reservation_id?: number | null } & Omit<GlassesDistanceForm, "reservation_id">>) : []);
    } catch (e) {
      toast({
        title: "Failed to load glasses records",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    } finally {
      setGlassesLoading(false);
    }
  };

  const stopScanner = () => {
    if (zxingControlsRef.current) {
      zxingControlsRef.current.stop();
      zxingControlsRef.current = null;
    }
    zxingReaderRef.current = null;
    if (videoRef.current) {
      const stream = videoRef.current.srcObject as MediaStream | null;
      if (stream) stream.getTracks().forEach((track) => track.stop());
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

  useEffect(() => () => stopScanner(), []);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold mb-1">Patients</h2>
          <p className="text-muted-foreground text-sm">Search patients and manage patient profile</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setAssignOpen(true)}>Assign Existing (Code / QR)</Button>
          <Button onClick={() => setAddOpen(true)} className="gradient-primary text-primary-foreground border-0 gap-2">
            <Plus className="h-4 w-4" />
            Add Patient
          </Button>
          <Button asChild variant="outline">
            <Link to="/clinic-dashboard/patients/new">Open Full Form</Link>
          </Button>
        </div>
      </div>

      <div className="relative max-w-sm mb-4">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search by name or phone..."
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
                <th className="text-start font-medium p-4 text-muted-foreground">Patient</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Phone</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Email</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Assigned Doctors</th>
                <th className="text-start font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {isLoading && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={6}>Loading patients...</td>
                </tr>
              )}
              {error && (
                <tr>
                  <td className="p-4 text-destructive" colSpan={6}>
                    {error instanceof Error ? error.message : "Failed to load patients"}
                  </td>
                </tr>
              )}
              {!isLoading && !error && paged.length === 0 && (
                <tr>
                  <td className="p-4 text-muted-foreground" colSpan={6}>No patients found.</td>
                </tr>
              )}
              {paged.map((p) => (
                <tr key={String(p.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(p.id)}</td>
                  <td className="p-4 font-medium">{p.name ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.phone ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">{p.email ?? "—"}</td>
                  <td className="p-4 text-muted-foreground">
                    {(p.assigned_doctor_names ?? []).length > 0
                      ? p.assigned_doctor_names?.join(", ")
                      : "—"}
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <Button asChild size="sm" variant="outline" className="gap-2">
                        <Link to={`/clinic-dashboard/patients/${p.id}/edit`}>
                          <Edit className="h-4 w-4" />
                          Edit
                        </Link>
                      </Button>
                      <Button asChild size="sm" variant="outline">
                        <Link to={`/clinic-dashboard/patients/${p.id}/history`}>History</Link>
                      </Button>
                      <Button asChild size="sm" className="gap-2 gradient-primary text-primary-foreground border-0">
                        <Link to={`/clinic-dashboard/reservations/new?patient_id=${p.id}`}>
                          <Plus className="h-4 w-4" />
                          Add Reservation
                        </Link>
                      </Button>
                      <Button size="sm" variant="outline" className="gap-2" onClick={() => void openPatientGlassesModal({ id: p.id, name: p.name })}>
                        <Glasses className="h-4 w-4" />
                        Glasses
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

      <Dialog open={addOpen} onOpenChange={setAddOpen}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>Add Patient</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div className="grid sm:grid-cols-2 gap-3">
              <div className="space-y-1">
                <Label>Name *</Label>
                <Input value={addForm.name} onChange={(e) => setAddForm((f) => ({ ...f, name: e.target.value }))} />
              </div>
              <div className="space-y-1">
                <Label>Phone *</Label>
                <Input value={addForm.phone} onChange={(e) => setAddForm((f) => ({ ...f, phone: e.target.value }))} />
              </div>
            </div>
            <div className="space-y-1">
              <Label>Address *</Label>
              <Input value={addForm.address} onChange={(e) => setAddForm((f) => ({ ...f, address: e.target.value }))} />
            </div>
            <div className="grid sm:grid-cols-2 gap-3">
              <div className="space-y-1">
                <Label>Email</Label>
                <Input value={addForm.email} onChange={(e) => setAddForm((f) => ({ ...f, email: e.target.value }))} />
              </div>
              <div className="space-y-1">
                <Label>Gender</Label>
                <select
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
            <div className="space-y-2">
              <Label>Assign Doctors *</Label>
              <div className="max-h-40 overflow-y-auto rounded-md border p-3 space-y-2">
                {doctors.map((d) => {
                  const doctorId = String(d.id);
                  const checked = addForm.doctor_ids.includes(doctorId);
                  return (
                    <label key={doctorId} className="flex items-center gap-2 text-sm cursor-pointer">
                      <Checkbox
                        checked={checked}
                        onCheckedChange={(value) => {
                          const isChecked = Boolean(value);
                          setAddForm((prev) => ({
                            ...prev,
                            doctor_ids: isChecked
                              ? Array.from(new Set([...prev.doctor_ids, doctorId]))
                              : prev.doctor_ids.filter((id) => id !== doctorId),
                          }));
                        }}
                      />
                      <span>{d.name ?? `Doctor ${d.id}`}</span>
                    </label>
                  );
                })}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAddOpen(false)}>Cancel</Button>
            <Button onClick={onSubmitAdd} disabled={createPatientMutation.isPending}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={glassesOpen} onOpenChange={setGlassesOpen}>
        <DialogContent className="sm:max-w-4xl">
          <DialogHeader>
            <DialogTitle>Glasses Distance {activePatient ? `for ${activePatient.name ?? `Patient #${activePatient.id}`}` : ""}</DialogTitle>
          </DialogHeader>
          {glassesLoading ? (
            <p className="text-sm text-muted-foreground">Loading glasses records...</p>
          ) : (
            <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
              <div className="space-y-1">
                <Label htmlFor="patient-glasses-reservation-id">Reservation ID (optional)</Label>
                <Input
                  id="patient-glasses-reservation-id"
                  placeholder="Leave empty if not linked to reservation"
                  value={glassesForm.reservation_id}
                  onChange={(e) => setGlassesForm((p) => ({ ...p, reservation_id: e.target.value }))}
                />
              </div>
              <div className="grid md:grid-cols-2 gap-4">
                <div className="space-y-3 rounded-lg border p-3">
                  <p className="text-sm font-medium">Distance (D)</p>
                  <div className="grid grid-cols-3 gap-2">
                    <Input placeholder="SPH R" value={glassesForm.SPH_R_D} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_R_D: e.target.value }))} />
                    <Input placeholder="CYL R" value={glassesForm.CYL_R_D} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_R_D: e.target.value }))} />
                    <Input placeholder="AX R" value={glassesForm.AX_R_D} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_R_D: e.target.value }))} />
                    <Input placeholder="SPH L" value={glassesForm.SPH_L_D} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_L_D: e.target.value }))} />
                    <Input placeholder="CYL L" value={glassesForm.CYL_L_D} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_L_D: e.target.value }))} />
                    <Input placeholder="AX L" value={glassesForm.AX_L_D} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_L_D: e.target.value }))} />
                  </div>
                </div>
                <div className="space-y-3 rounded-lg border p-3">
                  <p className="text-sm font-medium">Near (N)</p>
                  <div className="grid grid-cols-3 gap-2">
                    <Input placeholder="SPH R" value={glassesForm.SPH_R_N} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_R_N: e.target.value }))} />
                    <Input placeholder="CYL R" value={glassesForm.CYL_R_N} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_R_N: e.target.value }))} />
                    <Input placeholder="AX R" value={glassesForm.AX_R_N} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_R_N: e.target.value }))} />
                    <Input placeholder="SPH L" value={glassesForm.SPH_L_N} onChange={(e) => setGlassesForm((p) => ({ ...p, SPH_L_N: e.target.value }))} />
                    <Input placeholder="CYL L" value={glassesForm.CYL_L_N} onChange={(e) => setGlassesForm((p) => ({ ...p, CYL_L_N: e.target.value }))} />
                    <Input placeholder="AX L" value={glassesForm.AX_L_N} onChange={(e) => setGlassesForm((p) => ({ ...p, AX_L_N: e.target.value }))} />
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <p className="text-sm font-medium">Recent Records</p>
                {glassesHistory.length === 0 ? (
                  <p className="text-sm text-muted-foreground">No glasses records yet.</p>
                ) : (
                  <div className="space-y-2">
                    {glassesHistory.slice(0, 5).map((row) => (
                      <div key={String(row.id)} className="rounded-md border p-2 text-xs">
                        <p className="text-muted-foreground mb-1">
                          {row.created_at ?? "—"} {row.reservation_id ? `• Reservation #${row.reservation_id}` : ""}
                        </p>
                        <p>D: R({row.SPH_R_D || "—"}/{row.CYL_R_D || "—"}/{row.AX_R_D || "—"}) L({row.SPH_L_D || "—"}/{row.CYL_L_D || "—"}/{row.AX_L_D || "—"})</p>
                        <p>N: R({row.SPH_R_N || "—"}/{row.CYL_R_N || "—"}/{row.AX_R_N || "—"}) L({row.SPH_L_N || "—"}/{row.CYL_L_N || "—"}/{row.AX_L_N || "—"})</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}
          <DialogFooter>
            <Button variant="outline" onClick={() => setGlassesOpen(false)} disabled={savePatientGlassesMutation.isPending}>Cancel</Button>
            <Button onClick={() => savePatientGlassesMutation.mutate()} disabled={savePatientGlassesMutation.isPending || glassesLoading}>
              {savePatientGlassesMutation.isPending ? "Saving..." : "Save Glasses Data"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={assignOpen} onOpenChange={setAssignOpen}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader><DialogTitle>Assign Existing Patient</DialogTitle></DialogHeader>
          <div className="space-y-3">
            <div className="space-y-1">
              <Label>Patient Code</Label>
              <Input
                placeholder="Example: 20260001"
                value={assignForm.patient_code}
                onChange={(e) => setAssignForm((f) => ({ ...f, patient_code: e.target.value }))}
              />
            </div>
            <div className="space-y-1">
              <Label>QR Value (scanned text)</Label>
              <Input
                placeholder="Paste scanned QR content"
                value={assignForm.qr_value}
                onChange={(e) => setAssignForm((f) => ({ ...f, qr_value: e.target.value }))}
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
            <div className="space-y-2">
              <Label>Assign Doctors *</Label>
              <div className="max-h-40 overflow-y-auto rounded-md border p-3 space-y-2">
                {doctors.map((d) => {
                  const doctorId = String(d.id);
                  const checked = assignForm.doctor_ids.includes(doctorId);
                  return (
                    <label key={doctorId} className="flex items-center gap-2 text-sm cursor-pointer">
                      <Checkbox
                        checked={checked}
                        onCheckedChange={(value) => {
                          const isChecked = Boolean(value);
                          setAssignForm((prev) => ({
                            ...prev,
                            doctor_ids: isChecked
                              ? Array.from(new Set([...prev.doctor_ids, doctorId]))
                              : prev.doctor_ids.filter((id) => id !== doctorId),
                          }));
                        }}
                      />
                      <span>{d.name ?? `Doctor ${d.id}`}</span>
                    </label>
                  );
                })}
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setAssignOpen(false)}>Cancel</Button>
            <Button onClick={onSubmitAssign} disabled={assignPatientMutation.isPending}>Assign</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
