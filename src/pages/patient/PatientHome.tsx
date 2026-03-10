import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Building2, FlaskConical, ScanLine, FileText, Heart, Glasses, Star } from "lucide-react";
import { useAuth } from "@/contexts/AuthContext";
import { patientApi } from "@/lib/api";

type OrgItem = { id?: number | string; name?: string; image?: string | null; logo?: string | null; email?: string; phone?: string };
type FlatItem = { id?: number | string; date?: string; created_at?: string; comment?: string; rating?: number | string };
type IconComponent = (props: { className?: string }) => JSX.Element;

function SliderSection({ title, icon: Icon, items }: { title: string; icon: IconComponent; items: OrgItem[] }) {
  return (
    <section className="space-y-3">
      <div className="flex items-center gap-2">
        <Icon className="h-4 w-4 text-primary" />
        <h3 className="font-semibold">{title}</h3>
      </div>
      <div className="flex gap-3 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        {items.length === 0 && <div className="text-sm text-muted-foreground">No data available.</div>}
        {items.map((item) => (
          <div key={String(item.id ?? Math.random())} className="min-w-[220px] rounded-xl border bg-card p-4">
            <p className="font-medium line-clamp-1">{item.name ?? "Unknown"}</p>
            <p className="mt-1 text-xs text-muted-foreground line-clamp-1">{item.email ?? item.phone ?? "—"}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

function CountCard({ title, value, icon: Icon }: { title: string; value: number; icon: IconComponent }) {
  return (
    <div className="rounded-xl border bg-card p-4">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">{title}</p>
        <Icon className="h-4 w-4 text-primary" />
      </div>
      <p className="mt-2 text-2xl font-bold">{value}</p>
    </div>
  );
}

export default function PatientHome() {
  const { token, patient } = useAuth();

  const homeQuery = useQuery({
    queryKey: ["patient", "home", token],
    queryFn: () => patientApi.home(token!),
    enabled: !!token,
  });

  const prescriptionsQuery = useQuery({
    queryKey: ["patient", "prescriptions", token],
    queryFn: () => patientApi.prescriptions(token!),
    enabled: !!token,
  });

  const chronicQuery = useQuery({
    queryKey: ["patient", "chronic-diseases", token],
    queryFn: () => patientApi.chronicDiseases(token!),
    enabled: !!token,
  });

  const glassesQuery = useQuery({
    queryKey: ["patient", "glasses", token],
    queryFn: () => patientApi.glassesDistance(token!),
    enabled: !!token,
  });

  const reviewsQuery = useQuery({
    queryKey: ["patient", "reviews", token],
    queryFn: () => patientApi.reviews(token!),
    enabled: !!token,
  });

  const homeData = (homeQuery.data as { data?: unknown })?.data ?? homeQuery.data;
  const clinics = ((homeData as { clinics?: unknown[] })?.clinics ?? []) as OrgItem[];
  const labs = ((homeData as { medicalLaboratories?: unknown[] })?.medicalLaboratories ?? []) as OrgItem[];
  const radiology = ((homeData as { radiologyCenters?: unknown[] })?.radiologyCenters ?? []) as OrgItem[];

  const prescriptions = useMemo(() => {
    const root = (prescriptionsQuery.data as { data?: unknown })?.data ?? prescriptionsQuery.data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as FlatItem[];
  }, [prescriptionsQuery.data]);

  const chronic = useMemo(() => {
    const root = (chronicQuery.data as { data?: unknown })?.data ?? chronicQuery.data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as FlatItem[];
  }, [chronicQuery.data]);

  const glasses = useMemo(() => {
    const root = (glassesQuery.data as { data?: unknown })?.data ?? glassesQuery.data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as FlatItem[];
  }, [glassesQuery.data]);

  const reviews = useMemo(() => {
    const root = (reviewsQuery.data as { data?: unknown })?.data ?? reviewsQuery.data;
    return (Array.isArray(root) ? root : ((root as { data?: unknown[] })?.data ?? [])) as FlatItem[];
  }, [reviewsQuery.data]);

  const loading = homeQuery.isLoading || prescriptionsQuery.isLoading || chronicQuery.isLoading || glassesQuery.isLoading || reviewsQuery.isLoading;
  const error = homeQuery.error || prescriptionsQuery.error || chronicQuery.error || glassesQuery.error || reviewsQuery.error;

  return (
    <div className="space-y-5">
      <div>
        <h2 className="text-xl font-bold">Welcome, {patient?.name ?? "Patient"}</h2>
        <p className="text-sm text-muted-foreground mt-1">Your medical dashboard in mobile style</p>
      </div>

      {loading && <div className="text-sm text-muted-foreground">Loading dashboard...</div>}
      {error && <div className="rounded-lg bg-destructive/10 text-destructive p-3 text-sm">{error instanceof Error ? error.message : "Failed to load data"}</div>}

      {!loading && !error && (
        <>
          <SliderSection title="My Clinics" icon={Building2} items={clinics} />
          <SliderSection title="Medical Labs" icon={FlaskConical} items={labs} />
          <SliderSection title="Radiology Centers" icon={ScanLine} items={radiology} />

          <section className="grid grid-cols-2 gap-3">
            <CountCard title="Prescriptions" value={prescriptions.length} icon={FileText} />
            <CountCard title="Chronic Diseases" value={chronic.length} icon={Heart} />
            <CountCard title="Glasses" value={glasses.length} icon={Glasses} />
            <CountCard title="Reviews" value={reviews.length} icon={Star} />
          </section>
        </>
      )}
    </div>
  );
}
