import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import { ScanLine, MapPin, Star, Search } from "lucide-react";
import { Input } from "@/components/ui/input";
import { useState } from "react";

const mockCenters = [
  { id: "1", name: "Cairo Radiology Center", location: "Cairo, Maadi", rating: 4.8, services: "X-Ray, MRI, CT Scan" },
  { id: "2", name: "Nile Imaging", location: "Giza, Haram", rating: 4.6, services: "Ultrasound, Mammography, MRI" },
  { id: "3", name: "Alex Scan Center", location: "Alexandria", rating: 4.7, services: "CT, PET Scan, X-Ray" },
];

export default function RadiologyCentersPage() {
  const [search, setSearch] = useState("");
  const filtered = mockCenters.filter(c => c.name.toLowerCase().includes(search.toLowerCase()));

  return (
    <div className="container py-8 md:py-12">
      <h1 className="text-3xl font-bold">Radiology Centers</h1>
      <p className="text-muted-foreground mt-1 mb-8">Find imaging and radiology services</p>

      <div className="relative max-w-md mb-8">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input placeholder="Search centers..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filtered.map((center, i) => (
          <motion.div key={center.id} initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.05 }}>
            <Link to={`/radiology-centers/${center.id}`} className="group block">
              <div className="bg-card rounded-xl border shadow-card hover:shadow-elevated transition-shadow p-5">
                <div className="h-10 w-10 rounded-lg bg-sidebar-accent flex items-center justify-center mb-4">
                  <ScanLine className="h-5 w-5 text-primary" />
                </div>
                <h3 className="font-semibold text-lg group-hover:text-primary transition-colors">{center.name}</h3>
                <p className="text-sm text-muted-foreground mt-1">{center.services}</p>
                <div className="flex items-center gap-2 mt-3 text-sm text-muted-foreground">
                  <MapPin className="h-3.5 w-3.5" /> {center.location}
                </div>
                <div className="flex items-center gap-1 mt-2">
                  <Star className="h-4 w-4 text-warning fill-current" />
                  <span className="text-sm font-semibold">{center.rating}</span>
                </div>
              </div>
            </Link>
          </motion.div>
        ))}
      </div>
    </div>
  );
}
