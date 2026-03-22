import { useEffect } from "react";
import { triggerInAppNotificationFeedback } from "@/lib/notificationFeedback";

/**
 * Listens for postMessage from the push service worker so we can vibrate / beep from the
 * window context (desktop often ignores `vibrate` on `showNotification` in the SW).
 */
export function ServiceWorkerClientBridge() {
	useEffect(() => {
		if (typeof navigator === "undefined" || !("serviceWorker" in navigator)) return;

		const onMessage = (event: MessageEvent) => {
			if (event.data && typeof event.data === "object" && (event.data as { type?: string }).type === "SAHEH_PUSH_RECEIVED") {
				triggerInAppNotificationFeedback();
			}
		};

		navigator.serviceWorker.addEventListener("message", onMessage);
		return () => navigator.serviceWorker.removeEventListener("message", onMessage);
	}, []);

	return null;
}
