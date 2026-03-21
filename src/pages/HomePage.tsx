import { motion } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { Building2, Users, CalendarDays, Heart, ArrowRight, Search, MapPin, Star, FlaskConical, ScanLine, RefreshCcw } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { useLanguage } from "@/contexts/LanguageContext";
import { publicApi } from "@/lib/api";

type HomeOrg = {
  id?: number | string;
  name?: string;
  address?: string;
  location?: string;
  rating?: number | string;
  reviews_count?: number;
  specialty_name?: string;
  specialty?: string;
};

type HomeResponseShape = {
  data?: {
    clinics?: HomeOrg[];
    medicalLaboratories?: HomeOrg[];
    radiologyCenters?: HomeOrg[];
    doctors?: unknown[];
    reservations?: unknown[];
    patients?: unknown[];
    counts?: {
      clinics?: number;
      doctors?: number;
      reservations?: number;
      patients?: number;
      medical_laboratories?: number;
      radiology_centers?: number;
    };
  };
  clinics?: HomeOrg[];
  medicalLaboratories?: HomeOrg[];
  radiologyCenters?: HomeOrg[];
  doctors?: unknown[];
  reservations?: unknown[];
  patients?: unknown[];
};

export default function HomePage() {
  const location = useLocation();
  const navigate = useNavigate();
  const registrationMessage = (location.state as { registrationMessage?: string } | null)?.registrationMessage;
  const showRegistrationModal = Boolean((location.state as { registrationSuccess?: boolean } | null)?.registrationSuccess);
  const [open, setOpen] = useState(showRegistrationModal);
  const { t } = useLanguage();

  const homeQuery = useQuery({
    queryKey: ["public-home"],
    queryFn: () => publicApi.landingOverview(),
  });

  const fallbackClinicsQuery = useQuery({
    queryKey: ["public-home-clinics-fallback"],
    queryFn: () => publicApi.landingFeaturedClinics({ per_page: "6" }),
    enabled: homeQuery.isError,
  });

  useEffect(() => {
    setOpen(showRegistrationModal);
  }, [showRegistrationModal]);

  const handleModalOpenChange = (nextOpen: boolean) => {
    setOpen(nextOpen);
    if (!nextOpen && showRegistrationModal) {
      navigate(location.pathname, { replace: true });
    }
  };

  const homeData = useMemo(() => {
    const root = (homeQuery.data as HomeResponseShape | undefined)?.data ?? (homeQuery.data as HomeResponseShape | undefined);
    return root as HomeResponseShape["data"] | HomeResponseShape | undefined;
  }, [homeQuery.data]);

  const clinics = useMemo(() => {
    if (homeData && Array.isArray((homeData as HomeResponseShape).clinics)) {
      return ((homeData as HomeResponseShape).clinics ?? []) as HomeOrg[];
    }
    const fallbackRoot = (fallbackClinicsQuery.data as { data?: unknown } | undefined)?.data ?? fallbackClinicsQuery.data;
    if (Array.isArray(fallbackRoot)) return fallbackRoot as HomeOrg[];
    return ((fallbackRoot as { data?: HomeOrg[] } | undefined)?.data ?? []) as HomeOrg[];
  }, [homeData, fallbackClinicsQuery.data]);

  const counts = ((homeData as HomeResponseShape | undefined)?.counts ?? {}) as NonNullable<HomeResponseShape["data"]>["counts"];
  const clinicsCount = counts?.clinics ?? clinics.length;
  const doctorsCount = counts?.doctors ?? (Array.isArray((homeData as HomeResponseShape | undefined)?.doctors) ? (homeData as HomeResponseShape).doctors!.length : 0);
  const reservationsCount = counts?.reservations ?? (Array.isArray((homeData as HomeResponseShape | undefined)?.reservations) ? (homeData as HomeResponseShape).reservations!.length : 0);
  const patientsCount = counts?.patients ?? (Array.isArray((homeData as HomeResponseShape | undefined)?.patients) ? (homeData as HomeResponseShape).patients!.length : 0);
  const labsCount = counts?.medical_laboratories ?? 0;
  const radiologyCentersCount = counts?.radiology_centers ?? 0;

  const stats = [
    { label: "Clinics", value: clinicsCount, icon: Building2, color: "text-primary" },
    { label: "Doctors", value: doctorsCount, icon: Users, color: "text-secondary" },
    { label: "Reservations", value: reservationsCount, icon: CalendarDays, color: "text-accent" },
    { label: "Patients", value: patientsCount, icon: Heart, color: "text-success" },
    { label: "Labs", value: labsCount, icon: FlaskConical, color: "text-primary" },
    { label: "Radiology", value: radiologyCentersCount, icon: ScanLine, color: "text-secondary" },
  ];

  const featuredClinics = clinics.slice(0, 6);
  const isLoading = homeQuery.isLoading || fallbackClinicsQuery.isLoading;
  const hasError = homeQuery.isError && fallbackClinicsQuery.isError;

  return (
    <div className="pb-8">
      <Dialog open={open} onOpenChange={handleModalOpenChange}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Registration submitted successfully</DialogTitle>
            <DialogDescription>
              {registrationMessage || "Your organization registration is pending review."}
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button type="button" onClick={() => handleModalOpenChange(false)}>
              OK
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      {/* Hero */}
      <section className="relative overflow-hidden">
        <div className="absolute inset-0 gradient-hero opacity-[0.08]" />
        <div className="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-secondary/10 blur-3xl" />
        <div className="container relative py-20 md:py-32">
          <div className="grid lg:grid-cols-2 gap-10 items-center">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              className="max-w-2xl"
            >
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sidebar-accent text-primary text-sm font-medium mb-6">
                <span className="h-2 w-2 rounded-full bg-primary animate-pulse" />
                {t("trusted_by_patients")}
              </div>
              <h1 className="text-4xl md:text-6xl font-extrabold tracking-tight text-foreground leading-[1.1]">
                {t("find_the_right_doctor_book_in_seconds")}
              </h1>
              <p className="mt-5 text-lg text-muted-foreground max-w-lg">
                {t("browse_clinics_compare_ratings_and_book_appointments_instantly")}
              </p>
              <div className="mt-8 flex flex-col sm:flex-row gap-3">
                <Link to="/clinics">
                  <Button size="lg" className="gap-2 gradient-primary text-primary-foreground border-0 shadow-elevated">
                    <Search className="h-4 w-4" />
                    {t("find_a_clinic")}
                  </Button>
                </Link>
                <Link to="/patient/appointments">
                  <Button size="lg" variant="outline" className="gap-2">
                    {t("my_appointments")}
                  </Button>
                </Link>
              </div>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, y: 24 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.65, delay: 0.1 }}
              className="rounded-2xl border bg-card/80 backdrop-blur p-6 md:p-7 shadow-elevated"
            >
              <div className="flex items-center justify-between gap-3">
                <h3 className="text-lg font-semibold">Live platform data</h3>
                {(homeQuery.isFetching || fallbackClinicsQuery.isFetching) && (
                  <span className="text-xs text-muted-foreground">Refreshing...</span>
                )}
              </div>
              <p className="text-sm text-muted-foreground mt-1">Updated from public APIs to show current network activity.</p>
              <div className="mt-6 grid grid-cols-2 gap-3">
                {stats.slice(0, 4).map((stat) => (
                  <div key={stat.label} className="rounded-xl border bg-background p-4">
                    <stat.icon className={`h-4 w-4 ${stat.color}`} />
                    <p className="mt-3 text-2xl font-bold">{stat.value}</p>
                    <p className="text-xs text-muted-foreground mt-1">{stat.label}</p>
                  </div>
                ))}
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {hasError && (
        <section className="container mt-4">
          <div className="rounded-xl border border-destructive/20 bg-destructive/10 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p className="text-sm text-destructive">
              {homeQuery.error instanceof Error ? homeQuery.error.message : "Could not load home data."}
            </p>
            <Button
              size="sm"
              variant="outline"
              className="w-fit gap-2"
              onClick={() => {
                void homeQuery.refetch();
                void fallbackClinicsQuery.refetch();
              }}
            >
              <RefreshCcw className="h-4 w-4" />
              Retry
            </Button>
          </div>
        </section>
      )}

      {/* Stats */}
      <section className="container -mt-6 relative z-10">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {stats.map((stat, i) => (
            <motion.div
              key={stat.label}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.06, duration: 0.5 }}
              className="bg-card rounded-xl p-5 shadow-card border"
            >
              <stat.icon className={`h-6 w-6 ${stat.color} mb-3`} />
              <p className="text-2xl md:text-3xl font-bold text-foreground">
                {isLoading ? "..." : stat.value}
              </p>
              <p className="text-sm text-muted-foreground mt-1">{stat.label}</p>
            </motion.div>
          ))}
        </div>
      </section>

      {/* Featured Clinics */}
      <section className="container py-20">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-2xl md:text-3xl font-bold">{t("featured_clinics")}</h2>
            <p className="text-muted-foreground mt-1">{t("top_rated_clinics_near_you")}</p>
            </div>
          <Link to="/clinics">
            <Button variant="ghost" className="gap-1 text-primary">
              {t("view_all")} <ArrowRight className="h-4 w-4" />
            </Button>
          </Link>
        </div>
        {isLoading ? (
          <div className="grid md:grid-cols-3 gap-6">
            {Array.from({ length: 3 }).map((_, idx) => (
              <div key={`clinic-skeleton-${idx}`} className="bg-card rounded-xl border overflow-hidden shadow-card animate-pulse">
                <div className="h-40 bg-muted" />
                <div className="p-5 space-y-3">
                  <div className="h-4 w-2/3 bg-muted rounded" />
                  <div className="h-3 w-1/2 bg-muted rounded" />
                  <div className="h-3 w-3/4 bg-muted rounded" />
                </div>
              </div>
            ))}
          </div>
        ) : featuredClinics.length === 0 ? (
          <div className="rounded-xl border bg-card p-8 text-center">
            <p className="text-muted-foreground">No clinics available right now.</p>
          </div>
        ) : (
          <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
            {featuredClinics.map((clinic, i) => (
            <motion.div
              key={String(clinic.id ?? i)}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2 + i * 0.1 }}
            >
              <Link to={`/clinics/${clinic.id ?? ""}`} className="group block">
                <div className="bg-card rounded-xl border overflow-hidden shadow-card hover:shadow-elevated transition-shadow">
                  <div className="h-40 bg-muted flex items-center justify-center">
                    <Building2 className="h-12 w-12 text-muted-foreground/30" />
                  </div>
                  <div className="p-5">
                    <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{clinic.name ?? "Clinic"}</h3>
                    <p className="text-sm text-muted-foreground mt-1">{clinic.specialty_name ?? clinic.specialty ?? "General care"}</p>
                    <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                      <MapPin className="h-3.5 w-3.5" />
                      {clinic.address ?? clinic.location ?? "Location not available"}
                    </div>
                    <div className="flex items-center gap-2 mt-2">
                      <div className="flex items-center gap-1 text-warning">
                        <Star className="h-4 w-4 fill-current" />
                        <span className="text-sm font-semibold text-foreground">{clinic.rating ?? "4.7"}</span>
                      </div>
                      <span className="text-xs text-muted-foreground">({clinic.reviews_count ?? 0} reviews)</span>
                    </div>
                  </div>
                </div>
              </Link>
            </motion.div>
            ))}
          </div>
        )}
      </section>

      {/* CTA */}
      <section className="container pb-20">
        <div className="gradient-primary rounded-2xl p-8 md:p-12 text-center">
          <h2 className="text-2xl md:text-3xl font-bold text-primary-foreground">{t("ready_to_book_your_appointment")}</h2>
          <p className="text-primary-foreground/80 mt-3 max-w-md mx-auto">
            {t("join_thousands_of_patients_who_trust_medicaid_for_their_healthcare_needs")}
          </p>
          <div className="mt-6 flex flex-col sm:flex-row justify-center gap-3">
            <Link to="/clinics">
              <Button size="lg" variant="secondary" className="gap-2">
                {t("browse_clinics")}
              </Button>
            </Link>
            <Link to="/patient/appointments">
              <Button size="lg" variant="outline" className="gap-2 border-primary-foreground/30 text-primary hover:bg-primary-foreground/10">
                {t("sign_in")}
              </Button>
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
