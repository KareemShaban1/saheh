import { useEffect, useMemo, useState } from "react";
import { useIsFetching, useIsMutating } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";

export default function GlobalLoadingIndicator() {
  const isFetching = useIsFetching();
  const isMutating = useIsMutating();
  const isActive = useMemo(() => isFetching > 0 || isMutating > 0, [isFetching, isMutating]);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    let showTimer: number | null = null;
    let hideTimer: number | null = null;

    if (isActive) {
      // Small delay avoids flicker on very fast requests.
      showTimer = window.setTimeout(() => setVisible(true), 150);
    } else {
      // Keep indicator briefly for smoother perceived transitions.
      hideTimer = window.setTimeout(() => setVisible(false), 200);
    }

    return () => {
      if (showTimer) window.clearTimeout(showTimer);
      if (hideTimer) window.clearTimeout(hideTimer);
    };
  }, [isActive]);

  if (!visible) return null;

  return (
    <>
      <div className="fixed top-0 left-0 right-0 z-[9999] h-1 bg-transparent pointer-events-none">
        <div className="h-full w-full bg-primary/25 overflow-hidden">
          <div className="h-full w-1/3 bg-primary animate-[loading-bar_1.1s_ease-in-out_infinite]" />
        </div>
      </div>
      <div className="fixed inset-0 z-[9998] flex items-center justify-center bg-background/35 backdrop-blur-[1px] pointer-events-none">
        <div className="rounded-full bg-card border shadow-sm p-3">
          <Loader2 className="h-6 w-6 animate-spin text-primary" />
        </div>
      </div>
      <style>{`
        @keyframes loading-bar {
          0% { transform: translateX(-120%); }
          100% { transform: translateX(320%); }
        }
      `}</style>
    </>
  );
}
