import { useState } from "react";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { Building2, MapPin, Star, Search, Filter } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

const mockClinics = [
  { id: "1", name: "Cairo Medical Center", specialty: "General Medicine", location: "Cairo, Nasr City", rating: 4.8, reviews: 324 },
  { id: "2", name: "Nile Heart Clinic", specialty: "Cardiology", location: "Giza, Mohandessin", rating: 4.9, reviews: 187 },
  { id: "3", name: "Delta Eye Center", specialty: "Ophthalmology", location: "Alexandria, Sidi Gaber", rating: 4.7, reviews: 256 },
  { id: "4", name: "Sphinx Dental Care", specialty: "Dentistry", location: "Cairo, Heliopolis", rating: 4.6, reviews: 142 },
  { id: "5", name: "Maadi Children's Clinic", specialty: "Pediatrics", location: "Cairo, Maadi", rating: 4.8, reviews: 298 },
  { id: "6", name: "Pyramids Orthopedic Center", specialty: "Orthopedics", location: "Giza, Haram", rating: 4.5, reviews: 176 },
];

export default function ClinicsPage() {
  const [search, setSearch] = useState("");

  const filtered = mockClinics.filter(c =>
    c.name.toLowerCase().includes(search.toLowerCase()) ||
    c.specialty.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="container py-8 md:py-12">
      <div className="mb-8">
        <h1 className="text-3xl font-bold">Find a Clinic</h1>
        <p className="text-muted-foreground mt-1">Browse and filter clinics by specialty, area, and rating</p>
      </div>

      <div className="flex flex-col sm:flex-row gap-3 mb-8">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search by name or specialty..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-10"
          />
        </div>
        <Button variant="outline" className="gap-2">
          <Filter className="h-4 w-4" /> Filters
        </Button>
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filtered.map((clinic, i) => (
          <motion.div
            key={clinic.id}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.05 }}
          >
            <Link to={`/clinics/${clinic.id}`} className="group block">
              <div className="bg-card rounded-xl border shadow-card hover:shadow-elevated transition-shadow overflow-hidden">
                <div className="h-36 bg-muted flex items-center justify-center">
                  <Building2 className="h-10 w-10 text-muted-foreground/30" />
                </div>
                <div className="p-5">
                  <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{clinic.name}</h3>
                  <p className="text-sm text-muted-foreground mt-1">{clinic.specialty}</p>
                  <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                    <MapPin className="h-3.5 w-3.5" />
                    {clinic.location}
                  </div>
                  <div className="flex items-center justify-between mt-3">
                    <div className="flex items-center gap-1">
                      <Star className="h-4 w-4 text-warning fill-current" />
                      <span className="text-sm font-semibold">{clinic.rating}</span>
                      <span className="text-xs text-muted-foreground">({clinic.reviews})</span>
                    </div>
                    <Button size="sm" variant="ghost" className="text-primary text-xs">
                      Book Now
                    </Button>
                  </div>
                </div>
              </div>
            </Link>
          </motion.div>
        ))}
      </div>
    </div>
  );
}
