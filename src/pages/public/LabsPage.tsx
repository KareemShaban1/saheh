import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { FlaskConical, MapPin, Star, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { useState } from "react";

const mockLabs = [
  { id: "1", name: "Alpha Medical Lab", location: "Cairo, Nasr City", rating: 4.7, services: "Blood tests, Urinalysis, Hormones" },
  { id: "2", name: "BioLab Diagnostics", location: "Giza, Dokki", rating: 4.8, services: "PCR, Blood chemistry, Microbiology" },
  { id: "3", name: "Delta Lab Center", location: "Alexandria", rating: 4.6, services: "Full body checkup, Genetic testing" },
];

export default function LabsPage() {
  const [search, setSearch] = useState("");
  const filtered = mockLabs.filter(l => l.name.toLowerCase().includes(search.toLowerCase()));

  return (
    <div className="container py-8 md:py-12">
      <h1 className="text-3xl font-bold">Medical Laboratories</h1>
      <p className="text-muted-foreground mt-1 mb-8">Find accredited labs for your medical tests</p>

      <div className="relative max-w-md mb-8">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search labs..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filtered.map((lab, i) => (
          <motion.div key={lab.id} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <Link to={`/labs/${lab.id}`} className="group block">
              <div className="bg-card rounded-xl border shadow-card hover:shadow-elevated transition-shadow p-5">
                <div className="h-10 w-10 rounded-lg bg-sidebar-accent flex items-center justify-center mb-4">
                  <FlaskConical className="h-5 w-5 text-primary" />
                </div>
                <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{lab.name}</h3>
                <p className="text-sm text-muted-foreground mt-1">{lab.services}</p>
                <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                  <MapPin className="h-3.5 w-3.5" /> {lab.location}
                </div>
                <div className="flex items-center gap-1 mt-2">
                  <Star className="h-4 w-4 text-warning fill-current" />
                  <span className="text-sm font-semibold">{lab.rating}</span>
                </div>
              </div>
            </Link>
          </motion.div>
        ))}
      </div>
    </div>
  );
}
