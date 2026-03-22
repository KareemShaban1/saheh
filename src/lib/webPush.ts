import { request } from "@/lib/api";

export type PushSubscribeBody = {
	endpoint: string;
	keys: { p256dh: string; auth: string };
	content_encoding?: string;
};

function urlBase64ToUint8Array(base64String: string): Uint8Array {
	const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
	const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
	const rawData = window.atob(base64);
	const outputArray = new Uint8Array(rawData.length);
	for (let i = 0; i < rawData.length; ++i) {
		outputArray[i] = rawData.charCodeAt(i);
	}
	return outputArray;
}

async function fetchVapidPublicKey(): Promise<string | null> {
	const res = await request<{ configured?: boolean; public_key?: string | null }>("/push/vapid-public-key");
	if (!res.configured || !res.public_key) {
		return null;
	}
	return res.public_key;
}

async function postSubscription(kind: "organization" | "admin" | "patient", token: string, body: PushSubscribeBody): Promise<void> {
	if (kind === "organization") {
		await request("/organization/push/subscribe", { method: "POST", body, token });
	} else if (kind === "admin") {
		await request("/admin/push/subscribe", { method: "POST", body, token });
	} else {
		await request("/patient/push/subscribe", { method: "POST", body, token });
	}
}

/**
 * Registers for Web Push (browser permission + PushManager) and stores the subscription on the API.
 * Safe to call on every dashboard load; duplicates are upserted by endpoint server-side.
 */
export async function syncWebPushSubscription(kind: "organization" | "admin" | "patient", token: string): Promise<void> {
	if (typeof window === "undefined") return;
	if (!("serviceWorker" in navigator) || !("PushManager" in window)) return;

	const vapidPublic = await fetchVapidPublicKey();
	if (!vapidPublic) return;

	const reg = await navigator.serviceWorker.ready;

	let perm = Notification.permission;
	if (perm === "default") {
		perm = await Notification.requestPermission();
	}
	if (perm !== "granted") return;

	const appServerKey = urlBase64ToUint8Array(vapidPublic);
	let sub = await reg.pushManager.getSubscription();
	if (!sub) {
		sub = await reg.pushManager.subscribe({
			userVisibleOnly: true,
			applicationServerKey: appServerKey,
		});
	}

	const json = sub.toJSON() as {
		endpoint?: string;
		keys?: { p256dh?: string; auth?: string };
	};
	if (!json.endpoint || !json.keys?.p256dh || !json.keys?.auth) return;

	await postSubscription(kind, token, {
		endpoint: json.endpoint,
		keys: { p256dh: json.keys.p256dh, auth: json.keys.auth },
		content_encoding: "aesgcm",
	});
}
