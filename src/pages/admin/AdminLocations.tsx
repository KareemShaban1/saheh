import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { adminApi } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useToast } from "@/hooks/use-toast";

type Row = { id: string | number; name: string; parent: string; status: number };
type LocationType = "governorate" | "city" | "area";
type FormMode = "add" | "edit";

export default function AdminLocations() {
  const queryClient = useQueryClient();
  const { toast } = useToast();

  const governoratesQuery = useQuery({
    queryKey: ["admin", "governorates"],
    queryFn: () => adminApi.governorates(),
  });
  const citiesQuery = useQuery({
    queryKey: ["admin", "cities"],
    queryFn: () => adminApi.cities(),
  });
  const areasQuery = useQuery({
    queryKey: ["admin", "areas"],
    queryFn: () => adminApi.areas(),
  });

  const governorateRows = useMemo<Row[]>(() => {
    const root = (governoratesQuery.data as { data?: unknown })?.data ?? governoratesQuery.data;
    return (Array.isArray(root) ? root : []).map((item) => {
      const row = item as Record<string, unknown>;
      return { id: String(row.id ?? "—"), name: String(row.name ?? "—"), parent: "—", status: Number(row.status ?? 1) };
    });
  }, [governoratesQuery.data]);

  const cityRows = useMemo<Row[]>(() => {
    const root = (citiesQuery.data as { data?: unknown })?.data ?? citiesQuery.data;
    return (Array.isArray(root) ? root : []).map((item) => {
      const row = item as Record<string, unknown>;
      return {
        id: String(row.id ?? "—"),
        name: String(row.name ?? "—"),
        parent: String(row.governorate_name ?? row.governorate_id ?? "—"),
        status: Number(row.status ?? 1),
      };
    });
  }, [citiesQuery.data]);

  const areaRows = useMemo<Row[]>(() => {
    const root = (areasQuery.data as { data?: unknown })?.data ?? areasQuery.data;
    return (Array.isArray(root) ? root : []).map((item) => {
      const row = item as Record<string, unknown>;
      return { id: String(row.id ?? "—"), name: String(row.name ?? "—"), parent: String(row.city_name ?? row.city_id ?? "—"), status: Number(row.status ?? 1) };
    });
  }, [areasQuery.data]);

  const reload = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ["admin", "governorates"] }),
      queryClient.invalidateQueries({ queryKey: ["admin", "cities"] }),
      queryClient.invalidateQueries({ queryKey: ["admin", "areas"] }),
    ]);
  };

  const createGovernorateMutation = useMutation({
    mutationFn: (payload: { name: string }) => adminApi.createGovernorate({ ...payload, status: "active" }),
    onSuccess: async () => { await reload(); toast({ title: "Governorate created" }); },
  });
  const updateGovernorateMutation = useMutation({
    mutationFn: ({ id, name }: { id: string | number; name: string }) => adminApi.updateGovernorate(id, { name }),
    onSuccess: async () => { await reload(); toast({ title: "Governorate updated" }); },
  });
  const statusGovernorateMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: "active" | "inactive" }) => adminApi.updateGovernorateStatus(id, status),
    onSuccess: async () => { await reload(); toast({ title: "Governorate status updated" }); },
  });
  const deleteGovernorateMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteGovernorate(id),
    onSuccess: async () => { await reload(); toast({ title: "Governorate deleted" }); },
  });

  const createCityMutation = useMutation({
    mutationFn: (payload: { name: string; governorate_id: number }) => adminApi.createCity({ ...payload, status: "active" }),
    onSuccess: async () => { await reload(); toast({ title: "City created" }); },
  });
  const updateCityMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string | number; payload: { name: string; governorate_id: number } }) => adminApi.updateCity(id, payload),
    onSuccess: async () => { await reload(); toast({ title: "City updated" }); },
  });
  const statusCityMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: "active" | "inactive" }) => adminApi.updateCityStatus(id, status),
    onSuccess: async () => { await reload(); toast({ title: "City status updated" }); },
  });
  const deleteCityMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteCity(id),
    onSuccess: async () => { await reload(); toast({ title: "City deleted" }); },
  });

  const createAreaMutation = useMutation({
    mutationFn: (payload: { name: string; city_id: number; governorate_id: number }) => adminApi.createArea({ ...payload, status: "active" }),
    onSuccess: async () => { await reload(); toast({ title: "Area created" }); },
  });
  const updateAreaMutation = useMutation({
    mutationFn: ({ id, payload }: { id: string | number; payload: { name: string; city_id: number; governorate_id: number } }) => adminApi.updateArea(id, payload),
    onSuccess: async () => { await reload(); toast({ title: "Area updated" }); },
  });
  const statusAreaMutation = useMutation({
    mutationFn: ({ id, status }: { id: string | number; status: "active" | "inactive" }) => adminApi.updateAreaStatus(id, status),
    onSuccess: async () => { await reload(); toast({ title: "Area status updated" }); },
  });
  const deleteAreaMutation = useMutation({
    mutationFn: (id: string | number) => adminApi.deleteArea(id),
    onSuccess: async () => { await reload(); toast({ title: "Area deleted" }); },
  });

  const [detailsOpen, setDetailsOpen] = useState(false);
  const [detailsTitle, setDetailsTitle] = useState("Details");
  const [detailsRows, setDetailsRows] = useState<Array<{ label: string; value: string }>>([]);

  const [formOpen, setFormOpen] = useState(false);
  const [formMode, setFormMode] = useState<FormMode>("add");
  const [formType, setFormType] = useState<LocationType>("governorate");
  const [editingId, setEditingId] = useState<string | number | null>(null);
  const [formState, setFormState] = useState({
    name: "",
    governorate_id: "",
    city_id: "",
  });

  const openAddForm = (type: LocationType) => {
    setFormMode("add");
    setFormType(type);
    setEditingId(null);
    setFormState({ name: "", governorate_id: "", city_id: "" });
    setFormOpen(true);
  };

  const openEditForm = async (type: LocationType, row: Row) => {
    setFormMode("edit");
    setFormType(type);
    setEditingId(row.id);
    try {
      if (type === "governorate") {
        setFormState({ name: row.name, governorate_id: "", city_id: "" });
      } else if (type === "city") {
        const details = await adminApi.city(row.id);
        const root = (details as { data?: unknown })?.data ?? {};
        const d = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
        setFormState({
          name: String(d.name ?? row.name),
          governorate_id: String(d.governorate_id ?? ""),
          city_id: "",
        });
      } else {
        const details = await adminApi.area(row.id);
        const root = (details as { data?: unknown })?.data ?? {};
        const d = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
        setFormState({
          name: String(d.name ?? row.name),
          governorate_id: String(d.governorate_id ?? ""),
          city_id: String(d.city_id ?? ""),
        });
      }
      setFormOpen(true);
    } catch (e) {
      toast({
        title: "Failed to load edit details",
        description: e instanceof Error ? e.message : "Unknown error",
        variant: "destructive",
      });
    }
  };

  const saveForm = () => {
    const name = formState.name.trim();
    if (!name) {
      toast({ title: "Name is required", variant: "destructive" });
      return;
    }

    if (formType === "governorate") {
      if (formMode === "add") {
        createGovernorateMutation.mutate({ name }, { onSuccess: () => setFormOpen(false) });
      } else if (editingId) {
        updateGovernorateMutation.mutate({ id: editingId, name }, { onSuccess: () => setFormOpen(false) });
      }
      return;
    }

    const governorate_id = Number(formState.governorate_id);
    if (!Number.isFinite(governorate_id) || governorate_id <= 0) {
      toast({ title: "Governorate is required", variant: "destructive" });
      return;
    }

    if (formType === "city") {
      if (formMode === "add") {
        createCityMutation.mutate({ name, governorate_id }, { onSuccess: () => setFormOpen(false) });
      } else if (editingId) {
        updateCityMutation.mutate({ id: editingId, payload: { name, governorate_id } }, { onSuccess: () => setFormOpen(false) });
      }
      return;
    }

    const city_id = Number(formState.city_id);
    if (!Number.isFinite(city_id) || city_id <= 0) {
      toast({ title: "City is required", variant: "destructive" });
      return;
    }

    if (formMode === "add") {
      createAreaMutation.mutate({ name, city_id, governorate_id }, { onSuccess: () => setFormOpen(false) });
    } else if (editingId) {
      updateAreaMutation.mutate({ id: editingId, payload: { name, city_id, governorate_id } }, { onSuccess: () => setFormOpen(false) });
    }
  };

  const Table = ({
    rows,
    onShow,
    onEdit,
    onToggle,
    onDelete,
    onAdd,
  }: {
    rows: Row[];
    onShow: (id: string | number) => void;
    onEdit: (row: Row) => void;
    onToggle: (row: Row) => void;
    onDelete: (row: Row) => void;
    onAdd: () => void;
  }) => (
    <div>
      <div className="mb-4">
        <Button onClick={onAdd} className="gradient-primary text-primary-foreground border-0">Add</Button>
      </div>
      <div className="bg-card rounded-xl border shadow-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/50">
                <th className="text-left font-medium p-4 text-muted-foreground">#</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Name</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Parent</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Status</th>
                <th className="text-left font-medium p-4 text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {rows.map((row) => (
                <tr key={String(row.id)} className="hover:bg-muted/30 transition-colors">
                  <td className="p-4 text-muted-foreground">{String(row.id)}</td>
                  <td className="p-4">{row.name}</td>
                  <td className="p-4">{row.parent}</td>
                  <td className="p-4">
                    <Badge variant="secondary" className={row.status === 1 ? "bg-success/10 text-success" : "bg-muted text-muted-foreground"}>
                      {row.status === 1 ? "active" : "inactive"}
                    </Badge>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      <Button variant="outline" size="sm" onClick={() => onShow(row.id)}>Show</Button>
                      <Button variant="outline" size="sm" onClick={() => onEdit(row)}>Edit</Button>
                      <Button variant="outline" size="sm" onClick={() => onToggle(row)}>
                        {row.status === 1 ? "Deactivate" : "Activate"}
                      </Button>
                      <Button variant="destructive" size="sm" onClick={() => onDelete(row)}>Delete</Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );

  return (
    <div>
      <div className="mb-6">
        <h2 className="text-2xl font-bold">Locations</h2>
        <p className="text-muted-foreground text-sm mt-1">Manage governorates, cities, and areas</p>
      </div>

      <Tabs defaultValue="governorates">
        <TabsList className="mb-6">
          <TabsTrigger value="governorates">Governorates ({governorateRows.length})</TabsTrigger>
          <TabsTrigger value="cities">Cities ({cityRows.length})</TabsTrigger>
          <TabsTrigger value="areas">Areas ({areaRows.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="governorates">
          {governoratesQuery.isLoading ? (
            <div className="text-sm text-muted-foreground">Loading governorates...</div>
          ) : governoratesQuery.error ? (
            <div className="text-sm text-destructive">{governoratesQuery.error instanceof Error ? governoratesQuery.error.message : "Failed to load governorates"}</div>
          ) : (
            <Table
              rows={governorateRows}
              onAdd={() => openAddForm("governorate")}
              onShow={async (id) => {
                const res = await adminApi.governorate(id);
                const root = (res as { data?: unknown })?.data ?? {};
                const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
                setDetailsTitle("Governorate Details");
                setDetailsRows([
                  { label: "Name", value: String(row.name ?? "—") },
                  { label: "Status", value: Number(row.status ?? 1) === 1 ? "active" : "inactive" },
                ]);
                setDetailsOpen(true);
              }}
              onEdit={(row) => openEditForm("governorate", row)}
              onToggle={(row) => statusGovernorateMutation.mutate({ id: row.id, status: row.status === 1 ? "inactive" : "active" })}
              onDelete={(row) => window.confirm(`Delete "${row.name}"?`) && deleteGovernorateMutation.mutate(row.id)}
            />
          )}
        </TabsContent>

        <TabsContent value="cities">
          {citiesQuery.isLoading ? (
            <div className="text-sm text-muted-foreground">Loading cities...</div>
          ) : citiesQuery.error ? (
            <div className="text-sm text-destructive">{citiesQuery.error instanceof Error ? citiesQuery.error.message : "Failed to load cities"}</div>
          ) : (
            <Table
              rows={cityRows}
              onAdd={() => openAddForm("city")}
              onShow={async (id) => {
                const res = await adminApi.city(id);
                const root = (res as { data?: unknown })?.data ?? {};
                const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
                setDetailsTitle("City Details");
                setDetailsRows([
                  { label: "Name", value: String(row.name ?? "—") },
                  { label: "Governorate", value: String(row.governorate_name ?? row.governorate_id ?? "—") },
                  { label: "Status", value: Number(row.status ?? 1) === 1 ? "active" : "inactive" },
                ]);
                setDetailsOpen(true);
              }}
              onEdit={(row) => openEditForm("city", row)}
              onToggle={(row) => statusCityMutation.mutate({ id: row.id, status: row.status === 1 ? "inactive" : "active" })}
              onDelete={(row) => window.confirm(`Delete "${row.name}"?`) && deleteCityMutation.mutate(row.id)}
            />
          )}
        </TabsContent>

        <TabsContent value="areas">
          {areasQuery.isLoading ? (
            <div className="text-sm text-muted-foreground">Loading areas...</div>
          ) : areasQuery.error ? (
            <div className="text-sm text-destructive">{areasQuery.error instanceof Error ? areasQuery.error.message : "Failed to load areas"}</div>
          ) : (
            <Table
              rows={areaRows}
              onAdd={() => openAddForm("area")}
              onShow={async (id) => {
                const res = await adminApi.area(id);
                const root = (res as { data?: unknown })?.data ?? {};
                const row = (root && typeof root === "object" ? root : {}) as Record<string, unknown>;
                setDetailsTitle("Area Details");
                setDetailsRows([
                  { label: "Name", value: String(row.name ?? "—") },
                  { label: "City", value: String(row.city_name ?? row.city_id ?? "—") },
                  { label: "Governorate", value: String(row.governorate_name ?? row.governorate_id ?? "—") },
                  { label: "Status", value: Number(row.status ?? 1) === 1 ? "active" : "inactive" },
                ]);
                setDetailsOpen(true);
              }}
              onEdit={(row) => openEditForm("area", row)}
              onToggle={(row) => statusAreaMutation.mutate({ id: row.id, status: row.status === 1 ? "inactive" : "active" })}
              onDelete={(row) => window.confirm(`Delete "${row.name}"?`) && deleteAreaMutation.mutate(row.id)}
            />
          )}
        </TabsContent>
      </Tabs>

      <Dialog open={formOpen} onOpenChange={setFormOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {formMode === "add" ? "Add" : "Edit"}{" "}
              {formType === "governorate" ? "Governorate" : formType === "city" ? "City" : "Area"}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-2">
            <div className="space-y-2">
              <Label htmlFor="location-name">Name</Label>
              <Input
                id="location-name"
                value={formState.name}
                onChange={(e) => setFormState((f) => ({ ...f, name: e.target.value }))}
              />
            </div>
            {formType !== "governorate" ? (
              <div className="space-y-2">
                <Label htmlFor="location-governorate">Governorate</Label>
                <select
                  id="location-governorate"
                  title="Governorate"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={formState.governorate_id}
                  onChange={(e) => setFormState((f) => ({ ...f, governorate_id: e.target.value }))}
                >
                  <option value="">Select governorate</option>
                  {governorateRows.map((g) => (
                    <option key={String(g.id)} value={String(g.id)}>
                      {g.name}
                    </option>
                  ))}
                </select>
              </div>
            ) : null}
            {formType === "area" ? (
              <div className="space-y-2">
                <Label htmlFor="location-city">City</Label>
                <select
                  id="location-city"
                  title="City"
                  className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                  value={formState.city_id}
                  onChange={(e) => setFormState((f) => ({ ...f, city_id: e.target.value }))}
                >
                  <option value="">Select city</option>
                  {cityRows.map((c) => (
                    <option key={String(c.id)} value={String(c.id)}>
                      {c.name}
                    </option>
                  ))}
                </select>
              </div>
            ) : null}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setFormOpen(false)}>Cancel</Button>
            <Button onClick={saveForm}>Save</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={detailsOpen} onOpenChange={setDetailsOpen}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>{detailsTitle}</DialogTitle>
          </DialogHeader>
          <div className="space-y-2 text-sm">
            {detailsRows.map((row) => (
              <p key={row.label}>
                <span className="font-medium">{row.label}:</span> {row.value}
              </p>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDetailsOpen(false)}>Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
