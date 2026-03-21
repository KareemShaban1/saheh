import { Link, useParams } from "react-router-dom";
import { useEffect, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft } from "lucide-react";
import { publicApi } from "@/lib/api";
import { Dialog, DialogContent } from "@/components/ui/dialog";

type CenterRow = { id?: number | string; name?: string; address?: string; description?: string; rating?: number | string };
type MediaRow = { id: number; media_type: "reel" | "video" | "story"; title?: string; file_url: string };

export default function RadiologyCenterDetailPage() {
  const { id } = useParams<{ id: string }>();
  const centerId = Number(id ?? 0);

  const centerQuery = useQuery({
    queryKey: ["public", "radiology", "detail", centerId],
    queryFn: () => publicApi.landingRadiologyCenters({ per_page: "100" }),
    enabled: centerId > 0,
  });

  const mediaQuery = useQuery({
    queryKey: ["public", "media", "radiology", centerId],
    queryFn: () => publicApi.organizationMedia({ owner_type: "radiology_center", owner_id: centerId, limit: 30 }),
    enabled: centerId > 0,
  });

  const center = useMemo(() => {
    const root = (centerQuery.data as { data?: unknown } | undefined)?.data ?? centerQuery.data;
    const list = Array.isArray(root) ? (root as CenterRow[]) : (((root as { data?: CenterRow[] } | undefined)?.data ?? []) as CenterRow[]);
    return list.find((x) => Number(x.id) === centerId);
  }, [centerQuery.data, centerId]);

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
      <Link to="/radiology-centers" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"><ArrowLeft className="h-4 w-4" /> Back to Radiology Centers</Link>
      <div className="rounded-2xl border bg-card p-6">
        <h1 className="text-3xl font-bold">{center?.name ?? "Radiology Center"}</h1>
        <p className="text-muted-foreground mt-1">{center?.address ?? "Location not available"}</p>
        <p className="text-sm mt-3">{center?.description ?? "No description available."}</p>
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

