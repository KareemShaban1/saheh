import { Link, useParams } from "react-router-dom";
import { motion } from "framer-motion";
import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import {
  ArrowLeft,
  Building2,
  CalendarDays,
  CheckCircle2,
  Clock,
  MapPin,
  Phone,
  Star,
  Stethoscope,
  User,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { publicApi } from "@/lib/api";
import { Dialog, DialogContent } from "@/components/ui/dialog";

type ClinicData = {
  id: string;
  name: string;
  subtitle: string;
  location: string;
  phone: string;
  hours: string;
  rating: number;
  reviewsCount: number;
  monthlyPatients: string;
  established: string;
  about: string;
  services: string[];
  doctors: Array<{ id: string; name: string; specialty: string; nextSlot: string }>;
  reviews: Array<{ user: string; rating: number; comment: string; date: string }>;
};

const clinicDirectory: Record<string, ClinicData> = {
  "1": {
    id: "1",
    name: "Cairo Medical Center",
    subtitle: "General Medicine • Multi-specialty Care",
    location: "Cairo, Nasr City",
    phone: "+20 123 456 789",
    hours: "9:00 AM - 10:00 PM",
    rating: 4.8,
    reviewsCount: 324,
    monthlyPatients: "1,200+",
    established: "Since 2012",
    about:
      "Cairo Medical Center provides integrated outpatient care with experienced doctors, same-day consultations, and modern diagnostics in one location.",
    services: ["Internal Medicine", "Pediatrics", "Dermatology", "Family Checkups", "Preventive Screenings", "Chronic Care Follow-up"],
    doctors: [
      { id: "1", name: "Dr. Ahmed Hassan", specialty: "Internal Medicine", nextSlot: "Today, 3:00 PM" },
      { id: "2", name: "Dr. Sara Mohamed", specialty: "Dermatology", nextSlot: "Tomorrow, 10:00 AM" },
      { id: "3", name: "Dr. Omar Khalil", specialty: "Pediatrics", nextSlot: "Today, 5:30 PM" },
    ],
    reviews: [
      { user: "Mohamed A.", rating: 5, comment: "Excellent service and friendly staff.", date: "2 days ago" },
      { user: "Fatma S.", rating: 4, comment: "Good experience overall and short wait time.", date: "1 week ago" },
    ],
  },
};

export default function ClinicDetailPage() {
  const { id } = useParams<{ id: string }>();
  const clinic = clinicDirectory[id ?? ""] ?? clinicDirectory["1"];
  const clinicId = Number(id ?? clinic.id);

  const mediaQuery = useQuery({
    queryKey: ["public", "media", "clinic", clinicId],
    queryFn: () => publicApi.organizationMedia({ owner_type: "clinic", owner_id: clinicId, limit: 30 }),
    enabled: clinicId > 0,
  });

  const media = useMemo(() => {
    const root = (mediaQuery.data as { data?: unknown } | undefined)?.data ?? mediaQuery.data;
    return (Array.isArray(root) ? root : ((root as { data?: Array<{ id: number; media_type: string; title?: string; file_url: string }> } | undefined)?.data ?? []));
  }, [mediaQuery.data]);
  const stories = media.filter((item) => item.media_type === "story");
  const [activeStory, setActiveStory] = useState<(typeof stories)[number] | null>(null);

  useEffect(() => {
    if (!activeStory || stories.length === 0) return;
    const timer = window.setTimeout(() => {
      const idx = stories.findIndex((s) => s.id === activeStory.id);
      if (idx === -1 || idx === stories.length - 1) {
        setActiveStory(null);
        return;
      }
      setActiveStory(stories[idx + 1]);
    }, 8000);
    return () => window.clearTimeout(timer);
  }, [activeStory, stories]);

  return (
    <div className="container py-8 md:py-12">
      <Link to="/clinics" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6">
        <ArrowLeft className="h-4 w-4" /> Back to Clinics
      </Link>

      <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="grid gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <section className="bg-card rounded-2xl border shadow-card overflow-hidden">
            <div className="h-52 bg-gradient-to-r from-primary/15 via-primary/5 to-transparent p-6 flex items-end justify-between">
              <div className="space-y-2">
                <p className="inline-flex items-center rounded-full border bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground">
                  Professional Clinic Profile
                </p>
                <h1 className="text-3xl font-bold tracking-tight">{clinic.name}</h1>
                <p className="text-muted-foreground">{clinic.subtitle}</p>
              </div>
              <div className="hidden sm:flex h-16 w-16 rounded-2xl bg-background/80 border items-center justify-center">
                <Building2 className="h-8 w-8 text-primary" />
              </div>
            </div>
            <div className="p-6 space-y-5">
              <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                <span className="flex items-center gap-2"><MapPin className="h-4 w-4" /> {clinic.location}</span>
                <span className="flex items-center gap-2"><Phone className="h-4 w-4" /> {clinic.phone}</span>
                <span className="flex items-center gap-2"><Clock className="h-4 w-4" /> {clinic.hours}</span>
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Rating</p>
                  <p className="mt-1 font-semibold flex items-center gap-1">
                    <Star className="h-4 w-4 text-warning fill-current" /> {clinic.rating}
                  </p>
                </div>
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Reviews</p>
                  <p className="mt-1 font-semibold">{clinic.reviewsCount}+</p>
                </div>
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Patients / month</p>
                  <p className="mt-1 font-semibold">{clinic.monthlyPatients}</p>
                </div>
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Established</p>
                  <p className="mt-1 font-semibold">{clinic.established}</p>
                </div>
              </div>
              <p className="text-sm text-muted-foreground leading-relaxed">{clinic.about}</p>
            </div>
          </section>

          <section className="bg-card rounded-2xl border p-6">
            <h2 className="text-xl font-semibold">Featured Services</h2>
            <div className="mt-4 grid sm:grid-cols-2 gap-3">
              {clinic.services.map((service) => (
                <div key={service} className="rounded-xl border bg-muted/20 px-4 py-3 flex items-center gap-2 text-sm">
                  <CheckCircle2 className="h-4 w-4 text-success" />
                  <span>{service}</span>
                </div>
              ))}
            </div>
          </section>

          <section className="bg-card rounded-2xl border p-6">
            <div className="flex items-center justify-between gap-4">
              <h2 className="text-xl font-semibold">Our Doctors</h2>
              <p className="text-sm text-muted-foreground">Choose your preferred specialist</p>
            </div>
            <div className="mt-4 space-y-3">
              {clinic.doctors.map((doc) => (
                <Link key={doc.id} to={`/doctors/${doc.id}`} className="group block">
                  <div className="rounded-xl border p-4 flex items-center gap-4 hover:shadow-card transition-shadow">
                    <div className="h-12 w-12 rounded-full bg-sidebar-accent flex items-center justify-center">
                      <User className="h-5 w-5 text-primary" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-semibold group-hover:text-primary transition-colors">{doc.name}</p>
                      <p className="text-sm text-muted-foreground">{doc.specialty}</p>
                    </div>
                    <div className="text-right hidden sm:block">
                      <p className="text-xs text-muted-foreground">Next slot</p>
                      <p className="text-sm font-medium text-success">{doc.nextSlot}</p>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </section>

          <section className="bg-card rounded-2xl border p-6">
            <h2 className="text-xl font-semibold">Patient Reviews</h2>
            <div className="mt-4 space-y-3">
              {clinic.reviews.map((review) => (
                <div key={`${review.user}-${review.date}`} className="rounded-xl border p-4">
                  <div className="flex items-center justify-between gap-3">
                    <p className="font-medium">{review.user}</p>
                    <div className="flex items-center gap-1">
                      {Array.from({ length: review.rating }).map((_, index) => (
                        <Star key={`${review.user}-star-${index}`} className="h-3.5 w-3.5 text-warning fill-current" />
                      ))}
                    </div>
                  </div>
                  <p className="text-sm text-muted-foreground mt-2">{review.comment}</p>
                  <p className="text-xs text-muted-foreground mt-2">{review.date}</p>
                </div>
              ))}
            </div>
          </section>

          {stories.length > 0 && (
            <section className="bg-card rounded-2xl border p-6">
              <h2 className="text-xl font-semibold">Stories</h2>
              <div className="mt-4 flex gap-3 overflow-x-auto pb-1">
                {stories.map((story) => (
                  <button
                    key={story.id}
                    type="button"
                    onClick={() => setActiveStory(story)}
                    className="shrink-0 w-28 h-44 rounded-xl border bg-muted/20 p-2 text-left"
                  >
                    <p className="text-xs text-primary uppercase">Story</p>
                    <p className="text-sm mt-2 line-clamp-3">{story.title || "Untitled story"}</p>
                  </button>
                ))}
              </div>
            </section>
          )}

          <section className="bg-card rounded-2xl border p-6">
            <h2 className="text-xl font-semibold">Reels, Videos & Stories</h2>
            <div className="mt-4 grid sm:grid-cols-2 gap-4">
              {media.map((item) => (
                <div key={item.id} className="rounded-xl border overflow-hidden">
                  <video src={item.file_url} controls className="w-full h-56 object-cover bg-black" />
                  <div className="p-3">
                    <p className="text-xs text-primary uppercase">{item.media_type}</p>
                    <p className="font-medium">{item.title || "Untitled"}</p>
                  </div>
                </div>
              ))}
            </div>
          </section>
        </div>

        <aside className="space-y-4">
          <div className="bg-card rounded-2xl border shadow-card p-6 sticky top-20">
            <h3 className="font-semibold text-lg">Book an Appointment</h3>
            <p className="text-sm text-muted-foreground mt-2 mb-5">
              Reserve with your preferred doctor and receive confirmation details instantly.
            </p>
            <Link to="/patient/appointments" className="block">
              <Button className="w-full gradient-primary text-primary-foreground border-0">
                <CalendarDays className="h-4 w-4 mr-2" />
                Book Now
              </Button>
            </Link>
            <div className="mt-4 rounded-xl border bg-muted/20 p-3 text-xs text-muted-foreground flex items-start gap-2">
              <Stethoscope className="h-4 w-4 mt-0.5 text-primary" />
              <span>Need a recommendation? Start with Internal Medicine for first-time consultations.</span>
            </div>
          </div>
        </aside>
      </motion.div>
      <Dialog open={!!activeStory} onOpenChange={(open) => !open && setActiveStory(null)}>
        <DialogContent className="max-w-xl p-0 overflow-hidden">
          {activeStory && <video src={activeStory.file_url} controls autoPlay className="w-full h-[70vh] object-cover bg-black" />}
        </DialogContent>
      </Dialog>
    </div>
  );
}
