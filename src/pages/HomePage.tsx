import { motion } from "framer-motion";
import { useEffect, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { Building2, Users, CalendarDays, Heart, ArrowRight, Search, MapPin, Star } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

const stats = [
  { label: "Clinics", value: "2,400+", icon: Building2, color: "text-primary" },
  { label: "Doctors", value: "8,500+", icon: Users, color: "text-secondary" },
  { label: "Appointments Today", value: "12,000+", icon: CalendarDays, color: "text-accent" },
  { label: "Patients", value: "150K+", icon: Heart, color: "text-success" },
];

const featuredClinics = [
  { id: "1", name: "Cairo Medical Center", specialty: "General Medicine", location: "Cairo, Egypt", rating: 4.8, reviews: 324, image: "" },
  { id: "2", name: "Nile Heart Clinic", specialty: "Cardiology", location: "Giza, Egypt", rating: 4.9, reviews: 187, image: "" },
  { id: "3", name: "Delta Eye Center", specialty: "Ophthalmology", location: "Alexandria, Egypt", rating: 4.7, reviews: 256, image: "" },
];

export default function HomePage() {
  const location = useLocation();
  const navigate = useNavigate();
  const registrationMessage = (location.state as { registrationMessage?: string } | null)?.registrationMessage;
  const showRegistrationModal = Boolean((location.state as { registrationSuccess?: boolean } | null)?.registrationSuccess);
  const [open, setOpen] = useState(showRegistrationModal);

  useEffect(() => {
    setOpen(showRegistrationModal);
  }, [showRegistrationModal]);

  const handleModalOpenChange = (nextOpen: boolean) => {
    setOpen(nextOpen);
    if (!nextOpen && showRegistrationModal) {
      navigate(location.pathname, { replace: true });
    }
  };

  return (
    <div>
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
        <div className="absolute inset-0 gradient-hero opacity-[0.07]" />
        <div className="container relative py-20 md:py-32">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
            className="max-w-2xl"
          >
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sidebar-accent text-primary text-sm font-medium mb-6">
              <span className="h-2 w-2 rounded-full bg-primary animate-pulse" />
              Trusted by 150K+ patients
            </div>
            <h1 className="text-4xl md:text-6xl font-extrabold tracking-tight text-foreground leading-[1.1]">
              Find the right <span className="text-primary">doctor</span>, book in seconds
            </h1>
            <p className="mt-5 text-lg text-muted-foreground max-w-lg">
              Browse clinics, compare ratings, and book appointments instantly. Your health, simplified.
            </p>
            <div className="mt-8 flex flex-col sm:flex-row gap-3">
              <Link to="/clinics">
                <Button size="lg" className="gap-2 gradient-primary text-primary-foreground border-0 shadow-elevated">
                  <Search className="h-4 w-4" />
                  Find a Clinic
                </Button>
              </Link>
              <Link to="/patient/appointments">
                <Button size="lg" variant="outline" className="gap-2">
                  My Appointments
                  <ArrowRight className="h-4 w-4" />
                </Button>
              </Link>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Stats */}
      <section className="container -mt-6 relative z-10">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {stats.map((stat, i) => (
            <motion.div
              key={stat.label}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.1, duration: 0.5 }}
              className="bg-card rounded-xl p-5 shadow-card border"
            >
              <stat.icon className={`h-6 w-6 ${stat.color} mb-3`} />
              <p className="text-2xl md:text-3xl font-bold text-foreground">{stat.value}</p>
              <p className="text-sm text-muted-foreground mt-1">{stat.label}</p>
            </motion.div>
          ))}
        </div>
      </section>

      {/* Featured Clinics */}
      <section className="container py-20">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h2 className="text-2xl md:text-3xl font-bold">Featured Clinics</h2>
            <p className="text-muted-foreground mt-1">Top-rated clinics near you</p>
          </div>
          <Link to="/clinics">
            <Button variant="ghost" className="gap-1 text-primary">
              View all <ArrowRight className="h-4 w-4" />
            </Button>
          </Link>
        </div>
        <div className="grid md:grid-cols-3 gap-6">
          {featuredClinics.map((clinic, i) => (
            <motion.div
              key={clinic.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.2 + i * 0.1 }}
            >
              <Link to={`/clinics/${clinic.id}`} className="group block">
                <div className="bg-card rounded-xl border overflow-hidden shadow-card hover:shadow-elevated transition-shadow">
                  <div className="h-40 bg-muted flex items-center justify-center">
                    <Building2 className="h-12 w-12 text-muted-foreground/30" />
                  </div>
                  <div className="p-5">
                    <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{clinic.name}</h3>
                    <p className="text-sm text-muted-foreground mt-1">{clinic.specialty}</p>
                    <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                      <MapPin className="h-3.5 w-3.5" />
                      {clinic.location}
                    </div>
                    <div className="flex items-center gap-2 mt-2">
                      <div className="flex items-center gap-1 text-warning">
                        <Star className="h-4 w-4 fill-current" />
                        <span className="text-sm font-semibold text-foreground">{clinic.rating}</span>
                      </div>
                      <span className="text-xs text-muted-foreground">({clinic.reviews} reviews)</span>
                    </div>
                  </div>
                </div>
              </Link>
            </motion.div>
          ))}
        </div>
      </section>

      {/* CTA */}
      <section className="container pb-20">
        <div className="gradient-primary rounded-2xl p-8 md:p-12 text-center">
          <h2 className="text-2xl md:text-3xl font-bold text-primary-foreground">Ready to book your appointment?</h2>
          <p className="text-primary-foreground/80 mt-3 max-w-md mx-auto">
            Join thousands of patients who trust MediCare for their healthcare needs.
          </p>
          <div className="mt-6 flex flex-col sm:flex-row justify-center gap-3">
            <Link to="/clinics">
              <Button size="lg" variant="secondary" className="gap-2">
                Browse Clinics
              </Button>
            </Link>
            <Link to="/patient/appointments">
              <Button size="lg" variant="outline" className="gap-2 border-primary-foreground/30 text-primary hover:bg-primary-foreground/10">
                Sign In
              </Button>
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
