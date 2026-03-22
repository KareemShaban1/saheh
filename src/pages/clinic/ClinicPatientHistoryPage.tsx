import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft, Eye, Glasses } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { clinicApi } from "@/lib/api";
import { useLanguage } from "@/contexts/LanguageContext";

type GlassesDistanceRow = {
  id?: number | string;
  created_at?: string | null;
  SPH_R_D?: string | null;
  CYL_R_D?: string | null;
  AX_R_D?: string | null;
  SPH_L_D?: string | null;
  CYL_L_D?: string | null;
  AX_L_D?: string | null;
  SPH_R_N?: string | null;
  CYL_R_N?: string | null;
  AX_R_N?: string | null;
  SPH_L_N?: string | null;
  CYL_L_N?: string | null;
  AX_L_N?: string | null;
};

type DrugRow = {
  id?: number | string;
  drug_id?: number | string | null;
  name?: string | null;
  type?: string | null;
  dose?: string | null;
  frequency?: string | null;
  period?: string | null;
  notes?: string | null;
};

type PrescriptionBlock = {
  id?: number | string;
  title?: string | null;
  notes?: string | null;
  images?: string[];
  drugs?: DrugRow[];
};

type TeethBlock = {
  general_note?: string | null;
  next_session_plan?: string | null;
  items?: Array<{ id?: number | string; tooth_number?: number; tooth_note?: string | null }>;
};

type ReservationHistory = {
  id: number | string;
  type?: string | null;
  parent_id?: number | string | null;
  date?: string | null;
  time?: string | null;
  slot?: string | null;
  month?: string | null;
  reservation_number?: string | null;
  first_diagnosis?: string | null;
  final_diagnosis?: string | null;
  cost?: string | null;
  status?: string | null;
  acceptance?: string | null;
  payment?: string | null;
  doctor_name?: string | null;
  attachments?: string[];
  prescription?: PrescriptionBlock | null;
  prescriptions?: PrescriptionBlock[];
  chronic_diseases?: Array<{
    id?: number | string;
    name?: string | null;
    measure?: string | null;
    date?: string | null;
    notes?: string | null;
  }>;
  medical_analyses?: Array<{
    id?: number | string;
    date?: string | null;
    report?: string | null;
    payment?: string | null;
    cost?: string | null;
    doctor_name?: string | null;
    images?: string[];
  }>;
  rays?: Array<{
    id?: number | string;
    date?: string | null;
    report?: string | null;
    payment?: string | null;
    cost?: string | null;
    images?: string[];
  }>;
  payments?: Array<{
    id?: number | string;
    payment_date?: string | null;
    amount?: string | null;
    remaining?: string | null;
    payment_way?: string | null;
  }>;
  services?: Array<{
    id?: number | string;
    fee?: string | null;
    notes?: string | null;
    service_name?: string | null;
    service_price?: string | null;
    images?: string[];
  }>;
  glasses_distances?: GlassesDistanceRow[];
  teeth?: TeethBlock;
};

function SectionTitle({ children }: { children: React.ReactNode }) {
  return <h4 className="text-sm font-semibold text-foreground mt-4 mb-2 first:mt-0">{children}</h4>;
}

function ImageStrip({ urls }: { urls: string[] }) {
  if (!urls?.length) return null;
  return (
    <div className="flex flex-wrap gap-2 mt-2">
      {urls.map((url) => (
        <a key={url} href={url} target="_blank" rel="noreferrer" className="block">
          <img src={url} alt="" className="h-20 w-20 rounded-md border object-cover hover:opacity-90" />
        </a>
      ))}
    </div>
  );
}

function EmptyLine({ t }: { t: (k: string) => string }) {
  return <p className="text-sm text-muted-foreground text-start">{t("clinic.patient_history.no_items")}</p>;
}

function GlassesDistanceTable({ row, t }: { row: GlassesDistanceRow; t: (k: string) => string }) {
  const p = row;
  return (
    <div className="border rounded-lg overflow-hidden bg-card">
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead className="w-28 min-w-[7rem] text-start">{t("clinic.patient_history.eye")}</TableHead>
              <TableHead className="text-start">{t("clinic.patient_history.sph")}</TableHead>
              <TableHead className="text-start">{t("clinic.patient_history.cyl")}</TableHead>
              <TableHead className="text-start">{t("clinic.patient_history.axis")}</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableCell className="font-medium text-sm">{t("clinic.patient_history.distance_r")}</TableCell>
              <TableCell className="text-sm">{p.SPH_R_D ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.CYL_R_D ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.AX_R_D ?? "—"}</TableCell>
            </TableRow>
            <TableRow>
              <TableCell className="font-medium text-sm">{t("clinic.patient_history.distance_l")}</TableCell>
              <TableCell className="text-sm">{p.SPH_L_D ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.CYL_L_D ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.AX_L_D ?? "—"}</TableCell>
            </TableRow>
            <TableRow>
              <TableCell className="font-medium text-sm">{t("clinic.patient_history.near_r")}</TableCell>
              <TableCell className="text-sm">{p.SPH_R_N ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.CYL_R_N ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.AX_R_N ?? "—"}</TableCell>
            </TableRow>
            <TableRow>
              <TableCell className="font-medium text-sm">{t("clinic.patient_history.near_l")}</TableCell>
              <TableCell className="text-sm">{p.SPH_L_N ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.CYL_L_N ?? "—"}</TableCell>
              <TableCell className="text-sm">{p.AX_L_N ?? "—"}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  );
}

function PatientGlassesRecordCard({ row, t }: { row: GlassesDistanceRow; t: (k: string) => string }) {
  return (
    <div className="bg-card rounded-xl border p-5 space-y-4">
      <div className="flex items-start gap-3">
        <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
          <Glasses className="h-5 w-5 text-primary" />
        </div>
        <div className="text-start min-w-0">
          <h3 className="font-semibold">
            {t("clinic.patient_history.record_id")} #{String(row.id ?? "—")}
          </h3>
          <p className="text-xs text-muted-foreground">
            {t("clinic.patient_history.created_at")}: {row.created_at ?? "—"}
          </p>
        </div>
      </div>
      <GlassesDistanceTable row={row} t={t} />
    </div>
  );
}

type VisitPanelProps = {
  reservation: ReservationHistory;
  patientId: string | undefined;
  t: (k: string) => string;
};

function VisitHistoryDetailTabs({ reservation, patientId, t }: VisitPanelProps) {
  const rxList =
    Array.isArray(reservation.prescriptions) && reservation.prescriptions.length > 0
      ? reservation.prescriptions
      : reservation.prescription
        ? [reservation.prescription]
        : [];
  const chronic = reservation.chronic_diseases ?? [];
  const analyses = reservation.medical_analyses ?? [];
  const rays = reservation.rays ?? [];
  const pays = reservation.payments ?? [];
  const services = reservation.services ?? [];
  const glasses = reservation.glasses_distances ?? [];
  const teeth = reservation.teeth;
  const teethItems = teeth?.items ?? [];

  return (
    <Tabs defaultValue="overview" className="w-full">
      <TabsList className="flex flex-wrap h-auto gap-1 mb-3 justify-start">
        <TabsTrigger value="overview" className="text-xs sm:text-sm">
          {t("clinic.patient_history.visit_tab_overview")}
        </TabsTrigger>
        <TabsTrigger value="prescriptions" className="text-xs sm:text-sm">
          {t("clinic.patient_history.visit_tab_prescriptions")}
        </TabsTrigger>
        <TabsTrigger value="lab" className="text-xs sm:text-sm">
          {t("clinic.patient_history.visit_tab_lab")}
        </TabsTrigger>
        <TabsTrigger value="billing" className="text-xs sm:text-sm">
          {t("clinic.patient_history.visit_tab_billing")}
        </TabsTrigger>
        <TabsTrigger value="glasses_dental" className="text-xs sm:text-sm">
          {t("clinic.patient_history.visit_tab_glasses_dental")}
        </TabsTrigger>
      </TabsList>

      <TabsContent value="overview" className="mt-0 space-y-3 text-sm border rounded-lg p-3 bg-muted/20 text-start">
        <div className="flex flex-wrap gap-2">
          <Button asChild size="sm" variant="outline" className="gap-2">
            <Link to={`/clinic-dashboard/reservations/${reservation.id}?patient_id=${patientId}`}>
              <Eye className="h-4 w-4" />
              {t("clinic.patient_history.open_details")}
            </Link>
          </Button>
          {reservation.type ? (
            <Badge variant="outline">
              {t("clinic.patient_history.visit_type")}: {reservation.type}
            </Badge>
          ) : null}
          {reservation.month ? (
            <Badge variant="outline">
              {t("clinic.patient_history.month")}: {reservation.month}
            </Badge>
          ) : null}
          {reservation.acceptance ? (
            <Badge variant="outline">
              {t("clinic.patient_history.acceptance")}: {reservation.acceptance}
            </Badge>
          ) : null}
          {reservation.payment ? (
            <Badge variant="outline">
              {t("clinic.patient_history.payment")}: {reservation.payment}
            </Badge>
          ) : null}
          {reservation.cost ? (
            <Badge variant="outline">
              {t("clinic.patient_history.cost")}: {reservation.cost}
            </Badge>
          ) : null}
        </div>
        {(reservation.first_diagnosis || reservation.final_diagnosis) && (
          <div className="space-y-2 text-start">
            {reservation.first_diagnosis ? (
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.first_diagnosis")}: </span>
                <span className="whitespace-pre-wrap">{reservation.first_diagnosis}</span>
              </div>
            ) : null}
            {reservation.final_diagnosis ? (
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.final_diagnosis")}: </span>
                <span className="whitespace-pre-wrap">{reservation.final_diagnosis}</span>
              </div>
            ) : null}
          </div>
        )}
        {reservation.attachments && reservation.attachments.length > 0 ? (
          <div>
            <SectionTitle>{t("clinic.patient_history.attachments")}</SectionTitle>
            <ImageStrip urls={reservation.attachments} />
          </div>
        ) : null}
        <div>
          <SectionTitle>{t("clinic.patient_history.chronic_diseases")}</SectionTitle>
          {!chronic.length ? (
            <EmptyLine t={t} />
          ) : (
            <ul className="list-disc ps-6 space-y-1">
              {chronic.map((c) => (
                <li key={String(c.id)}>
                  <span className="font-medium">{c.name ?? "—"}</span>
                  {c.measure ? <span> — {c.measure}</span> : null}
                  {c.date ? <span className="text-muted-foreground"> ({c.date})</span> : null}
                  {c.notes ? <div className="text-muted-foreground text-xs whitespace-pre-wrap">{c.notes}</div> : null}
                </li>
              ))}
            </ul>
          )}
        </div>
      </TabsContent>

      <TabsContent value="prescriptions" className="mt-0 text-sm border rounded-lg p-3 bg-muted/20 text-start">
        <SectionTitle>{t("clinic.patient_history.prescriptions")}</SectionTitle>
        {rxList.length === 0 ? (
          <EmptyLine t={t} />
        ) : (
          <div className="space-y-3">
            {rxList.map((rx, rxIdx) => (
              <div key={String(rx.id ?? `rx-${rxIdx}`)} className="rounded-md border p-3 bg-background text-start">
                {rx.title ? <p className="font-medium text-start">{rx.title}</p> : null}
                {rx.notes ? (
                  <p className="text-muted-foreground whitespace-pre-wrap mt-1 text-start">{rx.notes}</p>
                ) : null}
                <ImageStrip urls={rx.images ?? []} />
                <p className="text-xs font-medium mt-2">{t("clinic.patient_history.drugs")}</p>
                {!rx.drugs?.length ? (
                  <EmptyLine t={t} />
                ) : (
                  <ul className="list-disc ps-6 space-y-1 mt-1">
                    {rx.drugs!.map((d) => (
                      <li key={String(d.id)}>
                        <span className="font-medium">{d.name ?? `#${d.drug_id}`}</span>
                        {d.dose ? <span> — {d.dose}</span> : null}
                        {d.frequency ? <span>, {d.frequency}</span> : null}
                        {d.period ? <span>, {d.period}</span> : null}
                        {d.type ? <span className="text-muted-foreground"> ({d.type})</span> : null}
                        {d.notes ? <div className="text-muted-foreground text-xs whitespace-pre-wrap">{d.notes}</div> : null}
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            ))}
          </div>
        )}
      </TabsContent>

      <TabsContent value="lab" className="mt-0 space-y-4 text-sm border rounded-lg p-3 bg-muted/20 text-start">
        <div>
          <SectionTitle>{t("clinic.patient_history.medical_analyses")}</SectionTitle>
          {!analyses.length ? (
            <EmptyLine t={t} />
          ) : (
            <div className="space-y-2">
              {analyses.map((a) => (
                <div key={String(a.id)} className="rounded-md border p-2 bg-background">
                  <div className="text-xs text-muted-foreground">
                    {t("clinic.patient_history.date_label")}: {a.date ?? "—"}
                    {a.doctor_name ? ` · ${a.doctor_name}` : ""}
                    {a.payment ? ` · ${a.payment}` : ""}
                    {a.cost ? ` · ${a.cost}` : ""}
                  </div>
                  {a.report ? (
                    <p className="whitespace-pre-wrap mt-1 text-start">{a.report}</p>
                  ) : (
                    <p className="text-muted-foreground text-xs mt-1 text-start">{t("clinic.patient_history.report")}: —</p>
                  )}
                  <ImageStrip urls={a.images ?? []} />
                </div>
              ))}
            </div>
          )}
        </div>
        <div>
          <SectionTitle>{t("clinic.patient_history.rays")}</SectionTitle>
          {!rays.length ? (
            <EmptyLine t={t} />
          ) : (
            <div className="space-y-2">
              {rays.map((r) => (
                <div key={String(r.id)} className="rounded-md border p-2 bg-background text-start">
                  <div className="text-xs text-muted-foreground text-start">
                    {t("clinic.patient_history.date_label")}: {r.date ?? "—"}
                    {r.payment ? ` · ${r.payment}` : ""}
                    {r.cost ? ` · ${r.cost}` : ""}
                  </div>
                  {r.report ? <p className="whitespace-pre-wrap mt-1 text-start">{r.report}</p> : null}
                  <ImageStrip urls={r.images ?? []} />
                </div>
              ))}
            </div>
          )}
        </div>
      </TabsContent>

      <TabsContent value="billing" className="mt-0 space-y-4 text-sm border rounded-lg p-3 bg-muted/20 text-start">
        <div>
          <SectionTitle>{t("clinic.patient_history.payment_records")}</SectionTitle>
          {!pays.length ? (
            <EmptyLine t={t} />
          ) : (
            <ul className="space-y-1">
              {pays.map((p) => (
                <li key={String(p.id)} className="rounded border px-2 py-1 bg-background text-start">
                  {p.payment_date ?? "—"} — {t("clinic.patient_history.amount")}: {p.amount ?? "—"}
                  {p.remaining != null && p.remaining !== "" ? (
                    <span>
                      {" "}
                      · {t("clinic.patient_history.remaining")}: {p.remaining}
                    </span>
                  ) : null}
                  {p.payment_way ? (
                    <span>
                      {" "}
                      · {t("clinic.patient_history.method")}: {p.payment_way}
                    </span>
                  ) : null}
                </li>
              ))}
            </ul>
          )}
        </div>
        <div>
          <SectionTitle>{t("clinic.patient_history.services")}</SectionTitle>
          {!services.length ? (
            <EmptyLine t={t} />
          ) : (
            <ul className="space-y-1">
              {services.map((s) => (
                <li key={String(s.id)} className="rounded border px-2 py-1 bg-background">
                  <span className="font-medium">{s.service_name ?? "—"}</span>
                  {s.fee ? (
                    <span>
                      {" "}
                      · {t("clinic.patient_history.fee")}: {s.fee}
                    </span>
                  ) : null}
                  {s.service_price ? (
                    <span className="text-muted-foreground text-xs"> (list: {s.service_price})</span>
                  ) : null}
                  {s.notes ? (
                    <div className="text-xs text-muted-foreground whitespace-pre-wrap text-start">{s.notes}</div>
                  ) : null}
                  <ImageStrip urls={s.images ?? []} />
                </li>
              ))}
            </ul>
          )}
        </div>
      </TabsContent>

      <TabsContent value="glasses_dental" className="mt-0 space-y-6 text-sm border rounded-lg p-3 bg-muted/20 text-start">
        <div>
          <SectionTitle>{t("clinic.patient_history.glasses")}</SectionTitle>
          {!glasses.length ? (
            <EmptyLine t={t} />
          ) : (
            <div className="space-y-4">
              {glasses.map((g, idx) => (
                <div key={String(g.id ?? `g-${idx}`)} className="space-y-2">
                  {g.created_at ? (
                    <p className="text-xs text-muted-foreground">
                      {t("clinic.patient_history.created_at")}: {g.created_at}
                    </p>
                  ) : null}
                  <GlassesDistanceTable row={g} t={t} />
                </div>
              ))}
            </div>
          )}
        </div>
        <div>
          <SectionTitle>{t("clinic.patient_history.teeth")}</SectionTitle>
          {!teethItems.length && !teeth?.general_note && !teeth?.next_session_plan ? (
            <EmptyLine t={t} />
          ) : (
            <div className="space-y-3 text-start">
              {teeth?.general_note ? (
                <p className="text-start">
                  <span className="text-muted-foreground">{t("clinic.patient_history.general_note")}: </span>
                  {teeth.general_note}
                </p>
              ) : null}
              {teeth?.next_session_plan ? (
                <p className="text-start">
                  <span className="text-muted-foreground">{t("clinic.patient_history.next_session")}: </span>
                  {teeth.next_session_plan}
                </p>
              ) : null}
              {teethItems.length > 0 ? (
                <div className="border rounded-lg overflow-hidden bg-background">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-24">{t("clinic.patient_history.tooth_number")}</TableHead>
                        <TableHead>{t("clinic.patient_history.notes")}</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {teethItems.map((it) => (
                        <TableRow key={String(it.id)}>
                          <TableCell className="font-medium">{it.tooth_number ?? "—"}</TableCell>
                          <TableCell>{it.tooth_note ?? "—"}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              ) : null}
            </div>
          )}
        </div>
      </TabsContent>
    </Tabs>
  );
}

export default function ClinicPatientHistoryPage() {
  const { id } = useParams<{ id: string }>();
  const { t, dir, lang } = useLanguage();
  const historyQuery = useQuery({
    queryKey: ["clinic", "patient-history", id],
    enabled: Boolean(id),
    queryFn: () => clinicApi.patientHistory(id as string),
  });

  const root = useMemo(() => {
    const raw = (historyQuery.data as { data?: unknown })?.data ?? historyQuery.data;
    return (raw && typeof raw === "object" ? raw : {}) as {
      patient?: {
        id?: number | string;
        name?: string;
        phone?: string;
        whatsapp_number?: string | null;
        email?: string;
        address?: string;
        age?: string;
        gender?: string;
        blood_group?: string;
        height?: string | null;
        weight?: string | null;
      };
      reservations?: ReservationHistory[];
      patient_level_glasses_distances?: Array<GlassesDistanceRow>;
      patient_level_tooth_records?: Array<{
        id: number | string;
        tooth_number: number;
        status?: string | null;
        notes?: string | null;
      }>;
    };
  }, [historyQuery.data]);

  const reservations = Array.isArray(root.reservations) ? root.reservations : [];
  const patient = root.patient ?? {};
  const patientGlasses = root.patient_level_glasses_distances ?? [];
  const legacyTeeth = root.patient_level_tooth_records ?? [];

  return (
    <div className="space-y-4" dir={dir} lang={lang}>
      <div className="flex items-center justify-between gap-3">
        <div className="text-start min-w-0 flex-1">
          <h2 className="text-2xl font-bold">{t("clinic.patient_history.title")}</h2>
          <p className="text-sm text-muted-foreground">{t("clinic.patient_history.description")}</p>
        </div>
        <Button asChild variant="outline" className="gap-2 shrink-0">
          <Link to="/clinic-dashboard/patients">
            <ArrowLeft className="h-4 w-4 rtl:rotate-180" aria-hidden />
            {t("clinic.patient_history.back_to_patients")}
          </Link>
        </Button>
      </div>

      {historyQuery.isLoading ? (
        <div className="rounded-lg border p-4 text-sm text-muted-foreground text-start">{t("clinic.patient_history.loading")}</div>
      ) : null}

      {historyQuery.error ? (
        <div className="rounded-lg border p-4 text-sm text-destructive text-start">
          {historyQuery.error instanceof Error ? historyQuery.error.message : "Failed to load patient history"}
        </div>
      ) : null}

      {!historyQuery.isLoading && !historyQuery.error ? (
        <>
          <div className="rounded-lg border p-4 text-ثىي">
            <h3 className="font-semibold mb-2">{t("clinic.patient_history.patient_info")}</h3>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.name")}:</span> {patient.name ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.phone")}:</span> {patient.phone ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.whatsapp")}:</span>{" "}
                {patient.whatsapp_number ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.email")}:</span> {patient.email ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.gender")}:</span> {patient.gender ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.age")}:</span> {patient.age ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.blood_group")}:</span>{" "}
                {patient.blood_group ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.height")}:</span> {patient.height ?? "—"}
              </div>
              <div className="text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.weight")}:</span> {patient.weight ?? "—"}
              </div>
              <div className="sm:col-span-2 lg:col-span-4 text-start">
                <span className="text-muted-foreground">{t("clinic.patient_history.address")}:</span> {patient.address ?? "—"}
              </div>
            </div>
          </div>

          <Tabs defaultValue="visits" className="space-y-4">
            <TabsList className="flex flex-wrap h-auto gap-1 w-full sm:w-auto justify-start">
              <TabsTrigger value="visits">{t("clinic.patient_history.tab_visits")}</TabsTrigger>
              <TabsTrigger value="glasses">{t("clinic.patient_history.tab_patient_glasses")}</TabsTrigger>
              <TabsTrigger value="teeth">{t("clinic.patient_history.tab_tooth_records")}</TabsTrigger>
            </TabsList>

            <TabsContent value="visits" className="space-y-3 mt-0 text-start">
              <h3 className="font-semibold sr-only">{t("clinic.patient_history.reservations")}</h3>
              {reservations.length === 0 ? (
                <div className="rounded-lg border p-4 text-sm text-muted-foreground">
                  {t("clinic.patient_history.no_reservations")}
                </div>
              ) : (
                <Accordion type="multiple" className="rounded-lg border px-3">
                  {reservations.map((reservation) => (
                    <AccordionItem key={String(reservation.id)} value={String(reservation.id)} className="border-b last:border-0">
                      <AccordionTrigger className="text-start hover:no-underline">
                        <div className="flex flex-col items-stretch gap-1 pe-2 text-start">
                          <div className="flex flex-wrap items-center gap-2">
                            <span className="font-medium">
                              #{String(reservation.id)}
                              {reservation.reservation_number ? ` · ${reservation.reservation_number}` : ""}
                            </span>
                            <span className="text-sm text-muted-foreground">
                              {reservation.date ?? "—"}
                              {reservation.time ? ` ${reservation.time}` : ""}
                              {reservation.slot && !reservation.time ? ` · ${reservation.slot}` : ""}
                            </span>
                            {reservation.status ? <Badge variant="secondary">{reservation.status}</Badge> : null}
                          </div>
                          <span className="text-sm text-muted-foreground">
                            {t("clinic.patient_history.doctor")}: {reservation.doctor_name ?? "—"}
                          </span>
                        </div>
                      </AccordionTrigger>
                      <AccordionContent>
                        <VisitHistoryDetailTabs reservation={reservation} patientId={id} t={t} />
                      </AccordionContent>
                    </AccordionItem>
                  ))}
                </Accordion>
              )}
            </TabsContent>

            <TabsContent value="glasses" className="mt-0 space-y-4">
              {!patientGlasses.length ? (
                <div className="rounded-lg border p-4 text-sm text-muted-foreground">
                  <EmptyLine t={t} />
                </div>
              ) : (
                patientGlasses.map((row) => <PatientGlassesRecordCard key={String(row.id)} row={row} t={t} />)
              )}
            </TabsContent>

            <TabsContent value="teeth" className="mt-0 text-start">
              {!legacyTeeth.length ? (
                <div className="rounded-lg border p-4 text-sm text-muted-foreground">
                  <EmptyLine t={t} />
                </div>
              ) : (
                <div className="border rounded-lg overflow-hidden bg-card">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-28">{t("clinic.patient_history.tooth_number")}</TableHead>
                        <TableHead>{t("clinic.patient_history.tooth_status")}</TableHead>
                        <TableHead>{t("clinic.patient_history.notes")}</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {legacyTeeth.map((tr) => (
                        <TableRow key={String(tr.id)}>
                          <TableCell className="font-medium">{tr.tooth_number}</TableCell>
                          <TableCell>{tr.status ?? "—"}</TableCell>
                          <TableCell className="text-muted-foreground">{tr.notes ?? "—"}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </TabsContent>
          </Tabs>
        </>
      ) : null}
    </div>
  );
}
