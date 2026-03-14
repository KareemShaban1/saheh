import { useEffect, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Building2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clinicApi } from "@/lib/api";
import { useToast } from "@/hooks/use-toast";

type ClinicSettingsPayload = {
  id?: number;
  name?: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  description?: string | null;
  website?: string | null;
  logo?: string | null;
  logo_url?: string | null;
  governorate_id?: number | null;
  city_id?: number | null;
  area_id?: number | null;
  specialty_id?: number | null;
  governorates?: Array<{ id: number; name: string }>;
  cities?: Array<{ id: number; name: string; governorate_id: number }>;
  areas?: Array<{ id: number; name: string; city_id: number; governorate_id: number }>;
  specialties?: Array<{ id: number; name?: string; name_en?: string; name_ar?: string }>;
};

export default function ClinicSettings() {
  const { toast } = useToast();
  const [form, setForm] = useState({
    name: "",
    email: "",
    phone: "",
    address: "",
    description: "",
    website: "",
    governorate_id: "",
    city_id: "",
    area_id: "",
    specialty_id: "",
  });
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [logoPreviewUrl, setLogoPreviewUrl] = useState<string | null>(null);
  const [serverLogoUrl, setServerLogoUrl] = useState<string | null>(null);
  const [governorates, setGovernorates] = useState<Array<{ id: number; name: string }>>([]);
  const [cities, setCities] = useState<Array<{ id: number; name: string; governorate_id: number }>>([]);
  const [areas, setAreas] = useState<Array<{ id: number; name: string; city_id: number; governorate_id: number }>>([]);
  const [specialties, setSpecialties] = useState<Array<{ id: number; name: string }>>([]);

  const settingsQuery = useQuery({
    queryKey: ["clinic", "settings"],
    queryFn: () => clinicApi.settings(),
  });

  useEffect(() => {
    const root = (settingsQuery.data as { data?: unknown })?.data ?? settingsQuery.data;
    const payload = (root ?? {}) as ClinicSettingsPayload;
    setForm({
      name: payload.name ?? "",
      email: payload.email ?? "",
      phone: payload.phone ?? "",
      address: payload.address ?? "",
      description: payload.description ?? "",
      website: payload.website ?? "",
      governorate_id: payload.governorate_id ? String(payload.governorate_id) : "",
      city_id: payload.city_id ? String(payload.city_id) : "",
      area_id: payload.area_id ? String(payload.area_id) : "",
      specialty_id: payload.specialty_id ? String(payload.specialty_id) : "",
    });
    setServerLogoUrl(payload.logo_url ?? null);
    setGovernorates(Array.isArray(payload.governorates) ? payload.governorates : []);
    setCities(Array.isArray(payload.cities) ? payload.cities : []);
    setAreas(Array.isArray(payload.areas) ? payload.areas : []);
    setSpecialties(
      Array.isArray(payload.specialties)
        ? payload.specialties.map((item) => ({ id: item.id, name: item.name || item.name_en || item.name_ar || `Specialty ${item.id}` }))
        : [],
    );
  }, [settingsQuery.data]);

  useEffect(() => {
    return () => {
      if (logoPreviewUrl) URL.revokeObjectURL(logoPreviewUrl);
    };
  }, [logoPreviewUrl]);

  const updateMutation = useMutation({
    mutationFn: () => {
      const payload = new FormData();
      payload.append("name", form.name.trim());
      payload.append("email", form.email.trim());
      payload.append("phone", form.phone.trim());
      payload.append("address", form.address.trim());
      payload.append("description", form.description.trim());
      payload.append("website", form.website.trim());
      payload.append("governorate_id", form.governorate_id);
      payload.append("city_id", form.city_id);
      payload.append("area_id", form.area_id);
      payload.append("specialty_id", form.specialty_id);
      if (logoFile) payload.append("logo", logoFile);
      return clinicApi.updateSettings(payload);
    },
    onSuccess: (res) => {
      const root = (res as { data?: unknown })?.data ?? res;
      const payload = (root ?? {}) as ClinicSettingsPayload;
      setServerLogoUrl(payload.logo_url ?? null);
      setLogoFile(null);
      if (logoPreviewUrl) URL.revokeObjectURL(logoPreviewUrl);
      setLogoPreviewUrl(null);
      toast({ title: "Clinic settings updated" });
    },
    onError: (e) =>
      toast({
        title: "Failed to update settings",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      }),
  });

  const displayedLogo = logoPreviewUrl || serverLogoUrl;
  const filteredCities = form.governorate_id
    ? cities.filter((city) => String(city.governorate_id) === form.governorate_id)
    : [];
  const filteredAreas = form.city_id
    ? areas.filter((area) => String(area.city_id) === form.city_id)
    : form.governorate_id
      ? areas.filter((area) => String(area.governorate_id) === form.governorate_id)
      : [];

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold">Clinic Settings</h2>
        <p className="text-sm text-muted-foreground mt-1">Update clinic details and logo</p>
      </div>

      {settingsQuery.isLoading ? <p className="text-sm text-muted-foreground">Loading settings...</p> : null}
      {settingsQuery.error ? (
        <p className="text-sm text-destructive">{settingsQuery.error instanceof Error ? settingsQuery.error.message : "Failed to load settings"}</p>
      ) : null}

      <div className="rounded-xl border bg-card p-4 space-y-5">
        <div className="space-y-2">
          <Label>Clinic Logo</Label>
          <div className="flex items-center gap-4">
            <div className="h-20 w-20 rounded-lg border bg-muted/30 flex items-center justify-center overflow-hidden">
              {displayedLogo ? (
                <img src={displayedLogo} alt="Clinic logo" className="h-full w-full object-cover" />
              ) : (
                <Building2 className="h-8 w-8 text-muted-foreground" />
              )}
            </div>
            <div className="space-y-2">
              <Input
                type="file"
                accept="image/*"
                onChange={(e) => {
                  const file = e.target.files?.[0] ?? null;
                  setLogoFile(file);
                  if (logoPreviewUrl) URL.revokeObjectURL(logoPreviewUrl);
                  setLogoPreviewUrl(file ? URL.createObjectURL(file) : null);
                }}
              />
              <p className="text-xs text-muted-foreground">PNG/JPG up to 4MB.</p>
            </div>
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>Name *</Label>
            <Input value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Email</Label>
            <Input type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Phone</Label>
            <Input value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} />
          </div>
          <div className="space-y-2">
            <Label>Website</Label>
            <Input value={form.website} onChange={(e) => setForm((f) => ({ ...f, website: e.target.value }))} placeholder="https://example.com" />
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>Specialty</Label>
            <Select
              value={form.specialty_id || "none"}
              onValueChange={(value) => setForm((f) => ({ ...f, specialty_id: value === "none" ? "" : value }))}
            >
              <SelectTrigger><SelectValue placeholder="Select specialty" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                {specialties.map((item) => (
                  <SelectItem key={item.id} value={String(item.id)}>
                    {item.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Governorate</Label>
            <Select
              value={form.governorate_id || "none"}
              onValueChange={(value) =>
                setForm((f) => ({
                  ...f,
                  governorate_id: value === "none" ? "" : value,
                  city_id: "",
                  area_id: "",
                }))
              }
            >
              <SelectTrigger><SelectValue placeholder="Select governorate" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                {governorates.map((item) => (
                  <SelectItem key={item.id} value={String(item.id)}>
                    {item.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>City</Label>
            <Select
              value={form.city_id || "none"}
              onValueChange={(value) =>
                setForm((f) => ({
                  ...f,
                  city_id: value === "none" ? "" : value,
                  area_id: "",
                }))
              }
              disabled={!form.governorate_id}
            >
              <SelectTrigger><SelectValue placeholder="Select city" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                {filteredCities.map((item) => (
                  <SelectItem key={item.id} value={String(item.id)}>
                    {item.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Area</Label>
            <Select
              value={form.area_id || "none"}
              onValueChange={(value) => setForm((f) => ({ ...f, area_id: value === "none" ? "" : value }))}
              disabled={!form.governorate_id}
            >
              <SelectTrigger><SelectValue placeholder="Select area" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                {filteredAreas.map((item) => (
                  <SelectItem key={item.id} value={String(item.id)}>
                    {item.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        <div className="space-y-2">
          <Label>Address</Label>
          <Input value={form.address} onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))} />
        </div>

        <div className="space-y-2">
          <Label>Description</Label>
          <Textarea rows={4} value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} />
        </div>

        <div className="flex justify-end">
          <Button
            onClick={() => updateMutation.mutate()}
            disabled={updateMutation.isPending || !form.name.trim()}
            className="gradient-primary text-primary-foreground border-0"
          >
            {updateMutation.isPending ? "Saving..." : "Save Changes"}
          </Button>
        </div>
      </div>
    </div>
  );
}
