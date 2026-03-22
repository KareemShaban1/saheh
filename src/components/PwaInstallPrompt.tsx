import { useCallback, useEffect, useState } from "react";
import { Download, Share } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useLanguage } from "@/contexts/LanguageContext";

type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
};

function isStandalone(): boolean {
  return (
    window.matchMedia("(display-mode: standalone)").matches ||
    window.matchMedia("(display-mode: window-controls-overlay)").matches ||
    (window.navigator as Navigator & { standalone?: boolean }).standalone === true
  );
}

function isIOS(): boolean {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === "MacIntel" && (navigator as Navigator & { maxTouchPoints?: number }).maxTouchPoints! > 1);
}

/** Compact install control for headers (Chrome/Edge/Android when installable). */
export function InstallPwaButton({ className }: { className?: string }) {
  const { t } = useLanguage();
  const [deferred, setDeferred] = useState<BeforeInstallPromptEvent | null>(null);
  const [hidden, setHidden] = useState(() => isStandalone());

  useEffect(() => {
    if (isStandalone()) {
      setHidden(true);
      return;
    }
    const onBip = (e: Event) => {
      e.preventDefault();
      setDeferred(e as BeforeInstallPromptEvent);
    };
    window.addEventListener("beforeinstallprompt", onBip);
    return () => window.removeEventListener("beforeinstallprompt", onBip);
  }, []);

  const install = useCallback(async () => {
    if (!deferred) return;
    await deferred.prompt();
    await deferred.userChoice;
    setDeferred(null);
  }, [deferred]);

  if (hidden) return null;

  if (deferred) {
    return (
      <Button type="button" variant="outline" size="sm" className={className} onClick={() => void install()}>
        <Download className="h-4 w-4 me-1 shrink-0" aria-hidden />
        {t("pwa.install_app")}
      </Button>
    );
  }

  if (isIOS()) {
    return (
      <Button type="button" variant="outline" size="sm" className={className} asChild>
        <a href="/#pwa-install">{t("pwa.ios_header_hint")}</a>
      </Button>
    );
  }

  return null;
}

/** Homepage card: install button + iOS “Add to Home Screen” hint. */
export function PwaInstallCard() {
  const { t, dir } = useLanguage();
  const [deferred, setDeferred] = useState<BeforeInstallPromptEvent | null>(null);
  const standalone = isStandalone();
  const ios = isIOS();

  useEffect(() => {
    if (isStandalone()) return;
    const onBip = (e: Event) => {
      e.preventDefault();
      setDeferred(e as BeforeInstallPromptEvent);
    };
    window.addEventListener("beforeinstallprompt", onBip);
    return () => window.removeEventListener("beforeinstallprompt", onBip);
  }, []);

  const install = useCallback(async () => {
    if (!deferred) return;
    await deferred.prompt();
    await deferred.userChoice;
    setDeferred(null);
  }, [deferred]);

  if (standalone) return null;

  const showChromeInstall = Boolean(deferred);
  const showIosHint = ios && !deferred;

  if (!showChromeInstall && !showIosHint) return null;

  return (
    <section id="pwa-install" className="container py-10 scroll-mt-24" dir={dir}>
      <div className="rounded-2xl border bg-card p-6 md:p-8 shadow-card flex flex-col md:flex-row md:items-center gap-6">
        <div className="h-16 w-16 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
          <img src="/pwa-192.png" alt="" width={56} height={56} className="rounded-xl" />
        </div>
        <div className="flex-1 text-start space-y-2">
          <h2 className="text-xl font-bold">{t("pwa.card_title")}</h2>
          <p className="text-sm text-muted-foreground">{t("pwa.card_description")}</p>
          {showIosHint ? (
            <p className="text-sm text-muted-foreground flex items-start gap-2">
              <Share className="h-4 w-4 mt-0.5 shrink-0 text-primary" aria-hidden />
              <span>{t("pwa.ios_install_hint")}</span>
            </p>
          ) : null}
        </div>
        {showChromeInstall ? (
          <Button type="button" size="lg" className="gap-2 shrink-0 gradient-primary text-primary-foreground border-0" onClick={() => void install()}>
            <Download className="h-4 w-4" aria-hidden />
            {t("pwa.install_app")}
          </Button>
        ) : null}
      </div>
    </section>
  );
}
