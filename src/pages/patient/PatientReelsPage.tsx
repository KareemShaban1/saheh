import { useEffect, useMemo, useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { patientApi } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Heart, Bookmark, Share2, Volume2, VolumeX } from "lucide-react";
import { useAuth } from "@/contexts/AuthContext";

type ReelItem = {
  id: number;
  owner_type: string;
  owner_id: number;
  title?: string | null;
  description?: string | null;
  file_url: string;
  likes_count?: number;
  saves_count?: number;
  liked_by_me?: boolean;
  saved_by_me?: boolean;
};

export default function PatientReelsPage() {
  const { token } = useAuth();
  const queryClient = useQueryClient();
  const containerRef = useRef<HTMLDivElement | null>(null);
  const videoRefs = useRef<Record<number, HTMLVideoElement | null>>({});
  const [mutedMap, setMutedMap] = useState<Record<number, boolean>>({});

  const reelsQuery = useQuery({
    queryKey: ["public", "reels"],
    queryFn: () => patientApi.reels(token!, { limit: 40 }),
    enabled: !!token,
  });

  const reels = useMemo(() => {
    const root = (reelsQuery.data as { data?: unknown } | undefined)?.data ?? reelsQuery.data;
    if (Array.isArray(root)) return root as ReelItem[];
    return ((root as { data?: ReelItem[] } | undefined)?.data ?? []) as ReelItem[];
  }, [reelsQuery.data]);

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          const target = entry.target as HTMLVideoElement;
          if (entry.isIntersecting && entry.intersectionRatio >= 0.7) {
            void target.play().catch(() => undefined);
          } else {
            target.pause();
          }
        });
      },
      {
        root: containerRef.current,
        threshold: [0.4, 0.7, 1],
      },
    );

    Object.values(videoRefs.current).forEach((video) => {
      if (video) observer.observe(video);
    });

    return () => observer.disconnect();
  }, [reels]);

  const toggleLikeMutation = useMutation({
    mutationFn: (reelId: number) => patientApi.toggleReelLike(token!, reelId),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ["public", "reels"] }),
  });

  const toggleSaveMutation = useMutation({
    mutationFn: (reelId: number) => patientApi.toggleReelSave(token!, reelId),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ["public", "reels"] }),
  });

  const shareReel = async (reel: ReelItem) => {
    const shareData = {
      title: reel.title || "Healthcare Reel",
      text: reel.description || "Check out this reel",
      url: reel.file_url,
    };
    try {
      if (navigator.share) {
        await navigator.share(shareData);
      } else {
        await navigator.clipboard.writeText(reel.file_url);
      }
    } catch {
      // user cancelled or unavailable
    }
  };

  return (
    <div className="h-[90dvh] bg-black">

      {reelsQuery.isError && (
        <div className="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
          {reelsQuery.error instanceof Error ? reelsQuery.error.message : "Failed to load reels."}
        </div>
      )}

      <div
        ref={containerRef}
        className="h-full overflow-y-auto snap-y snap-mandatory [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
      >
        {reels.map((reel) => (
          <div key={reel.id} className="snap-start h-full min-h-full relative overflow-hidden bg-black">
            <video
              ref={(el) => {
                videoRefs.current[reel.id] = el;
              }}
              src={reel.file_url}
              controls={false}
              muted={mutedMap[reel.id] ?? true}
              playsInline
              loop
              className="w-full h-full object-contain bg-black"
              onClick={(e) => {
                const v = e.currentTarget;
                if (v.paused) {
                  void v.play().catch(() => undefined);
                } else {
                  v.pause();
                }
              }}
            />
            <div className="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-black/75 via-black/35 to-transparent text-white">
              <p className="font-semibold line-clamp-1">{reel.title || "Untitled reel"}</p>
              <p className="text-xs text-white/80 mt-1 line-clamp-2">{reel.description || "No description"}</p>
            </div>

            <div className="absolute right-3 bottom-24 flex flex-col gap-2">
              <Button size="icon" variant="secondary" className="rounded-full h-10 w-10 bg-black/45 border border-white/20 text-white hover:bg-black/60" onClick={() => toggleLikeMutation.mutate(reel.id)}>
                <Heart className={`h-4 w-4 ${reel.liked_by_me ? "fill-current text-red-400" : ""}`} />
              </Button>
              <span className="text-[11px] text-white text-center">{reel.likes_count ?? 0}</span>

              <Button size="icon" variant="secondary" className="rounded-full h-10 w-10 bg-black/45 border border-white/20 text-white hover:bg-black/60" onClick={() => toggleSaveMutation.mutate(reel.id)}>
                <Bookmark className={`h-4 w-4 ${reel.saved_by_me ? "fill-current text-primary" : ""}`} />
              </Button>
              <span className="text-[11px] text-white text-center">{reel.saves_count ?? 0}</span>

              <Button size="icon" variant="secondary" className="rounded-full h-10 w-10 bg-black/45 border border-white/20 text-white hover:bg-black/60" onClick={() => void shareReel(reel)}>
                <Share2 className="h-4 w-4" />
              </Button>

              <Button
                size="icon"
                variant="secondary"
                className="rounded-full h-10 w-10 bg-black/45 border border-white/20 text-white hover:bg-black/60"
                onClick={() =>
                  setMutedMap((prev) => {
                    const next = { ...prev, [reel.id]: !(prev[reel.id] ?? true) };
                    const video = videoRefs.current[reel.id];
                    if (video) video.muted = next[reel.id];
                    return next;
                  })
                }
              >
                {(mutedMap[reel.id] ?? true) ? <VolumeX className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />}
              </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

