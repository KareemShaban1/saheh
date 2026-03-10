import { Link, useParams } from "react-router-dom";
import { motion } from "framer-motion";
import {
  ArrowLeft,
  Award,
  CalendarDays,
  CheckCircle2,
  Clock,
  GraduationCap,
  Languages,
  MapPin,
  Star,
  Stethoscope,
  UserRound,
} from "lucide-react";
import { Button } from "@/components/ui/button";

type DoctorProfile = {
  id: string;
  name: string;
  specialty: string;
  clinicName: string;
  location: string;
  rating: number;
  reviewsCount: number;
  patientsTreated: string;
  experience: string;
  about: string;
  expertise: string[];
  education: string[];
  languages: string[];
  schedule: Array<{ day: string; time: string }>;
  reviews: Array<{ user: string; rating: number; comment: string; date: string }>;
};

const doctorsDirectory: Record<string, DoctorProfile> = {
  "1": {
    id: "1",
    name: "Dr. Ahmed Hassan",
    specialty: "Internal Medicine Consultant",
    clinicName: "Cairo Medical Center",
    location: "Cairo, Nasr City",
    rating: 4.9,
    reviewsCount: 186,
    patientsTreated: "4,500+",
    experience: "12 years",
    about:
      "Dr. Ahmed focuses on evidence-based preventive care and chronic disease management with clear communication and personalized treatment plans.",
    expertise: ["Diabetes & Hypertension Follow-up", "General Adult Checkups", "Respiratory Conditions", "Digestive Health", "Preventive Screenings"],
    education: ["MD, Internal Medicine - Cairo University", "Board Certified in Internal Medicine", "Advanced Cardio-metabolic Care Diploma"],
    languages: ["Arabic", "English"],
    schedule: [
      { day: "Saturday", time: "3:00 PM - 8:00 PM" },
      { day: "Sunday", time: "2:00 PM - 7:00 PM" },
      { day: "Monday", time: "3:00 PM - 9:00 PM" },
      { day: "Tuesday", time: "3:00 PM - 8:00 PM" },
    ],
    reviews: [
      { user: "Youssef M.", rating: 5, comment: "Very professional and takes time to explain the treatment clearly.", date: "3 days ago" },
      { user: "Nour H.", rating: 5, comment: "Accurate diagnosis and smooth follow-up process.", date: "2 weeks ago" },
    ],
  },
};

export default function DoctorDetailPage() {
  const { id } = useParams<{ id: string }>();
  const doctor = doctorsDirectory[id ?? ""] ?? doctorsDirectory["1"];

  return (
    <div className="container py-8 md:py-12">
      <Link to="/clinics/1" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground mb-6">
        <ArrowLeft className="h-4 w-4" /> Back to Clinic
      </Link>

      <motion.div initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="grid gap-8 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <section className="bg-card rounded-2xl border shadow-card overflow-hidden">
            <div className="h-52 bg-gradient-to-r from-primary/15 via-primary/5 to-transparent p-6 flex items-end justify-between">
              <div className="space-y-2">
                <p className="inline-flex items-center rounded-full border bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground">
                  Verified Doctor Profile
                </p>
                <h1 className="text-3xl font-bold tracking-tight">{doctor.name}</h1>
                <p className="text-muted-foreground">{doctor.specialty}</p>
              </div>
              <div className="h-16 w-16 rounded-2xl bg-background/80 border items-center justify-center hidden sm:flex">
                <UserRound className="h-8 w-8 text-primary" />
              </div>
            </div>
            <div className="p-6 space-y-5">
              <div className="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                <span className="flex items-center gap-2"><Stethoscope className="h-4 w-4" /> {doctor.clinicName}</span>
                <span className="flex items-center gap-2"><MapPin className="h-4 w-4" /> {doctor.location}</span>
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Rating</p>
                  <p className="mt-1 font-semibold flex items-center gap-1">
                    <Star className="h-4 w-4 text-warning fill-current" /> {doctor.rating}
                  </p>
                </div>
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Reviews</p>
                  <p className="mt-1 font-semibold">{doctor.reviewsCount}+</p>
                </div>
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Patients Treated</p>
                  <p className="mt-1 font-semibold">{doctor.patientsTreated}</p>
                </div>
                <div className="rounded-xl border bg-muted/20 p-3">
                  <p className="text-xs text-muted-foreground">Experience</p>
                  <p className="mt-1 font-semibold">{doctor.experience}</p>
                </div>
              </div>
              <p className="text-sm text-muted-foreground leading-relaxed">{doctor.about}</p>
            </div>
          </section>

          <section className="bg-card rounded-2xl border p-6">
            <h2 className="text-xl font-semibold">Areas of Expertise</h2>
            <div className="mt-4 grid sm:grid-cols-2 gap-3">
              {doctor.expertise.map((item) => (
                <div key={item} className="rounded-xl border bg-muted/20 px-4 py-3 flex items-center gap-2 text-sm">
                  <CheckCircle2 className="h-4 w-4 text-success" />
                  <span>{item}</span>
                </div>
              ))}
            </div>
          </section>

          <section className="bg-card rounded-2xl border p-6">
            <h2 className="text-xl font-semibold">Qualifications & Languages</h2>
            <div className="mt-4 grid md:grid-cols-2 gap-4">
              <div className="rounded-xl border p-4">
                <p className="font-medium flex items-center gap-2">
                  <GraduationCap className="h-4 w-4 text-primary" />
                  Education & Certifications
                </p>
                <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                  {doctor.education.map((degree) => (
                    <li key={degree} className="flex items-start gap-2">
                      <Award className="h-4 w-4 mt-0.5 text-primary" />
                      <span>{degree}</span>
                    </li>
                  ))}
                </ul>
              </div>
              <div className="rounded-xl border p-4">
                <p className="font-medium flex items-center gap-2">
                  <Languages className="h-4 w-4 text-primary" />
                  Spoken Languages
                </p>
                <div className="mt-3 flex flex-wrap gap-2">
                  {doctor.languages.map((language) => (
                    <span key={language} className="rounded-full border bg-muted/20 px-3 py-1 text-xs">
                      {language}
                    </span>
                  ))}
                </div>
              </div>
            </div>
          </section>

          <section className="bg-card rounded-2xl border p-6">
            <h2 className="text-xl font-semibold">Patient Reviews</h2>
            <div className="mt-4 space-y-3">
              {doctor.reviews.map((review) => (
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
        </div>

        <aside className="space-y-4">
          <div className="bg-card rounded-2xl border shadow-card p-6 sticky top-20">
            <h3 className="font-semibold text-lg">Availability & Booking</h3>
            <p className="text-sm text-muted-foreground mt-2">Reserve your consultation in a few steps.</p>
            <div className="mt-4 space-y-2">
              {doctor.schedule.map((slot) => (
                <div key={slot.day} className="rounded-xl border bg-muted/20 p-3 text-sm flex items-start justify-between gap-3">
                  <span className="font-medium">{slot.day}</span>
                  <span className="text-muted-foreground flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5" />
                    {slot.time}
                  </span>
                </div>
              ))}
            </div>
            <Link to="/patient/appointments" className="block mt-5">
              <Button className="w-full gradient-primary text-primary-foreground border-0">
                <CalendarDays className="h-4 w-4 mr-2" />
                Book with {doctor.name.split(" ")[1]}
              </Button>
            </Link>
          </div>
        </aside>
      </motion.div>
    </div>
  );
}
