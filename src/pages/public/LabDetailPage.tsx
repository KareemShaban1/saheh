import { Link, useParams } from "react-router-dom";
import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft } from "lucide-react";
import { publicApi } from "@/lib/api";
import { Dialog, DialogContent } from "@/components/ui/dialog";

type LabRow = { id?: number | string; name?: string; address?: string; description?: string; rating?: number | string };
type MediaRow = { id: number; media_type: "reel" | "video" | "story"; title?: string; file_url: string };

export default function LabDetailPage() {
  const { id } = useParams<{ id: string }>();
  const labId = Number(id ?? 0);

  const labQuery = useQuery({
    queryKey: ["public", "labs", "detail", labId],
    queryFn: () => publicApi.landingMedicalLabs({ per_page: "100" }),
    enabled: labId > 0,
  });

  const mediaQuery = useQuery({
    queryKey: ["public", "media", "lab", labId],
    queryFn: () => publicApi.organizationMedia({ owner_type: "lab", owner_id: labId, limit: 30 }),
    enabled: labId > 0,
  });

  const lab = useMemo(() => {
    const root = (labQuery.data as { data?: unknown } | undefined)?.data ?? labQuery.data;
    const list = Array.isArray(root) ? (root as LabRow[]) : (((root as { data?: LabRow[] } | undefined)?.data ?? []) as LabRow[]);
    return list.find((x) => Number(x.id) === labId);
  }, [labQuery.data, labId]);

  const media = useMemo(() => {
    const root = (mediaQuery.data as { data?: unknown } | undefined)?.data ?? mediaQuery.data;
    return (Array.isArray(root) ? root : ((root as { data?: MediaRow[] } | undefined)?.data ?? [])) as MediaRow[];
  }, [mediaQuery.data]);
  const stories = media.filter((item) => item.media_type === "story");
  const [activeStory, setActiveStory] = useState<MediaRow | null>(null);

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
    <div className="container py-8 space-y-6">
      <Link to="/labs" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"><ArrowLeft className="h-4 w-4" /> Back to Labs</Link>
      <div className="rounded-2xl border bg-card p-6">
        <h1 className="text-3xl font-bold">{lab?.name ?? "Medical Lab"}</h1>
        <p className="text-muted-foreground mt-1">{lab?.address ?? "Location not available"}</p>
        <p className="text-sm mt-3">{lab?.description ?? "No description available."}</p>
      </div>
      <section className="rounded-2xl border bg-card p-6">
        {stories.length > 0 && (
          <>
            <h2 className="text-xl font-semibold">Stories</h2>
            <div className="mt-4 mb-6 flex gap-3 overflow-x-auto pb-1">
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
          </>
        )}
        <h2 className="text-xl font-semibold">Reels, Videos & Stories</h2>
        <div className="mt-4 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
          {media.map((item) => (
            <div key={item.id} className="rounded-xl border overflow-hidden">
              <video src={item.file_url} controls className="w-full h-60 object-cover bg-black" />
              <div className="p-3">
                <p className="text-xs text-primary uppercase">{item.media_type}</p>
                <p className="font-medium line-clamp-1">{item.title || "Untitled"}</p>
              </div>
            </div>
          ))}
        </div>
      </section>
      <Dialog open={!!activeStory} onOpenChange={(open) => !open && setActiveStory(null)}>
        <DialogContent className="max-w-xl p-0 overflow-hidden">
          {activeStory && <video src={activeStory.file_url} controls autoPlay className="w-full h-[70vh] object-cover bg-black" />}
        </DialogContent>
      </Dialog>
    </div>
  );
}

