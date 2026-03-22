import { useEffect, useRef } from "react";
import { triggerInAppNotificationFeedback } from "@/lib/notificationFeedback";

/**
 * When React Query refetches and new unread notification IDs appear, play a short tone + vibrate (if supported).
 * Skips the first successful fetch so existing unread items don’t buzz on page load.
 *
 * @param unreadIdsKey Sorted comma-separated unread ids (stable when data unchanged).
 */
export function useNewUnreadNotificationPing(unreadIdsKey: string, enabled: boolean, isFetched: boolean): void {
	const initializedRef = useRef(false);
	const prevKeyRef = useRef<string>("");

	useEffect(() => {
		if (!enabled || !isFetched) return;

		if (!initializedRef.current) {
			initializedRef.current = true;
			prevKeyRef.current = unreadIdsKey;
			return;
		}

		if (unreadIdsKey === prevKeyRef.current) return;

		const prev = prevKeyRef.current.length ? prevKeyRef.current.split(",") : [];
		const next = unreadIdsKey.length ? unreadIdsKey.split(",") : [];
		const prevSet = new Set(prev);
		const hasNewId = next.some((id) => id.length > 0 && !prevSet.has(id));

		prevKeyRef.current = unreadIdsKey;

		if (hasNewId) {
			triggerInAppNotificationFeedback();
		}
	}, [enabled, isFetched, unreadIdsKey]);
}
