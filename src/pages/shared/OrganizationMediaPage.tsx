import { FormEvent, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { clinicApi, getOrganizationUser, organizationMediaApi } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useToast } from "@/hooks/use-toast";

type MediaItem = {
  id: number;
  media_type: "reel" | "video" | "story";
  title?: string | null;
  description?: string | null;
  file_url: string;
};

export default function OrganizationMediaPage() {
  const queryClient = useQueryClient();
  const { toast } = useToast();
  const organizationUser = (getOrganizationUser() ?? {}) as { organization_guard?: string };
  const isClinic = organizationUser.organization_guard === "clinic";
  const [mediaType, setMediaType] = useState<"reel" | "video" | "story">("reel");
  const [targetType, setTargetType] = useState<"organization" | "doctor">("organization");
  const [targetId, setTargetId] = useState<string>("");
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [file, setFile] = useState<File | null>(null);

  const mediaQuery = useQuery({
    queryKey: ["organization", "media"],
    queryFn: () => organizationMediaApi.list(),
  });

  const items = useMemo(() => {
    const root = (mediaQuery.data as { data?: unknown } | undefined)?.data ?? mediaQuery.data;
    if (Array.isArray(root)) return root as MediaItem[];
    return ((root as { data?: MediaItem[] } | undefined)?.data ?? []) as MediaItem[];
  }, [mediaQuery.data]);

  const doctorsQuery = useQuery({
    queryKey: ["clinic", "doctors", "media-targets"],
    queryFn: () => clinicApi.doctors(),
    enabled: isClinic,
  });

  const doctors = useMemo(() => {
    const root = (doctorsQuery.data as { data?: unknown } | undefined)?.data ?? doctorsQuery.data;
    if (Array.isArray(root)) return root as Array<{ id?: number | string; name?: string }>;
    return ((root as { data?: Array<{ id?: number | string; name?: string }> } | undefined)?.data ?? []) as Array<{ id?: number | string; name?: string }>;
  }, [doctorsQuery.data]);

  const uploadMutation = useMutation({
    mutationFn: async () => {
      if (!file) throw new Error("Please select a video file.");
      const form = new FormData();
      form.append("media_type", mediaType);
      form.append("target_type", targetType);
      if (targetType === "doctor" && targetId) form.append("target_id", targetId);
      form.append("title", title);
      form.append("description", description);
      form.append("file", file);
      return organizationMediaApi.upload(form);
    },
    onSuccess: () => {
      setTitle("");
      setDescription("");
      setFile(null);
      toast({ title: "Media uploaded successfully" });
      void queryClient.invalidateQueries({ queryKey: ["organization", "media"] });
    },
    onError: (error) => {
      toast({ title: "Upload failed", description: error instanceof Error ? error.message : "Please try again.", variant: "destructive" });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => organizationMediaApi.remove(id),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ["organization", "media"] }),
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();
    uploadMutation.mutate();
  };

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold">Media Module</h2>
        <p className="text-sm text-muted-foreground mt-1">Upload reels, videos, and stories for your organization profile.</p>
      </div>

      <form onSubmit={onSubmit} className="rounded-xl border bg-card p-4 grid md:grid-cols-2 gap-3">
        <select
          value={mediaType}
          onChange={(e) => setMediaType(e.target.value as "reel" | "video" | "story")}
          className="h-10 rounded-md border bg-background px-3 text-sm"
        >
          <option value="reel">Reel</option>
          <option value="video">Video</option>
          <option value="story">Story</option>
        </select>
        {isClinic ? (
          <select
            value={targetType}
            onChange={(e) => {
              const next = e.target.value as "organization" | "doctor";
              setTargetType(next);
              if (next === "organization") setTargetId("");
            }}
            className="h-10 rounded-md border bg-background px-3 text-sm"
          >
            <option value="organization">Organization Profile</option>
            <option value="doctor">Doctor Profile</option>
          </select>
        ) : (
          <div />
        )}
        {isClinic && targetType === "doctor" && (
          <div className="md:col-span-2">
            <select
              value={targetId}
              onChange={(e) => setTargetId(e.target.value)}
              className="h-10 w-full rounded-md border bg-background px-3 text-sm"
            >
              <option value="">Select doctor</option>
              {doctors.map((doctor) => (
                <option key={String(doctor.id)} value={String(doctor.id)}>
                  {doctor.name ?? `Doctor #${doctor.id}`}
                </option>
              ))}
            </select>
          </div>
        )}
        <Input placeholder="Title" value={title} onChange={(e) => setTitle(e.target.value)} />
        <div className="md:col-span-2">
          <Input placeholder="Description" value={description} onChange={(e) => setDescription(e.target.value)} />
        </div>
        <div className="md:col-span-2">
          <Input type="file" accept="video/*" onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
        </div>
        <div className="md:col-span-2">
          <Button type="submit" disabled={uploadMutation.isPending}>{uploadMutation.isPending ? "Uploading..." : "Upload"}</Button>
        </div>
      </form>

      <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        {items.map((item) => (
          <div key={item.id} className="rounded-xl border bg-card overflow-hidden">
            <video src={item.file_url} controls className="w-full h-64 object-cover bg-black" />
            <div className="p-3">
              <p className="text-xs text-primary uppercase">{item.media_type}</p>
              <p className="font-medium">{item.title || "Untitled"}</p>
              <p className="text-xs text-muted-foreground mt-1 line-clamp-2">{item.description || "No description"}</p>
              <Button size="sm" variant="destructive" className="mt-3" onClick={() => deleteMutation.mutate(item.id)}>Delete</Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

