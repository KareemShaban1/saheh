/// <reference lib="webworker" />
/* eslint-disable no-restricted-globals */
import { clientsClaim, skipWaiting } from "workbox-core";
import { cleanupOutdatedCaches, createHandlerBoundToURL, precacheAndRoute } from "workbox-precaching";
import { NavigationRoute, registerRoute } from "workbox-routing";

declare const self: ServiceWorkerGlobalScope & {
  __WB_MANIFEST: Array<string | { url: string; revision: string | null }>;
};

// After deploy, activate the new SW immediately so users don’t keep an old precache until all tabs close.
skipWaiting();
clientsClaim();

precacheAndRoute(self.__WB_MANIFEST);
cleanupOutdatedCaches();

try {
  const handler = createHandlerBoundToURL("/index.html");
  const navigationRoute = new NavigationRoute(handler);
  registerRoute(navigationRoute);
} catch {
  // Precache may not include index in some dev setups; app still works online.
}

self.addEventListener("push", (event: PushEvent) => {
  let data: { title?: string; body?: string; url?: string; tag?: string } = {};
  try {
    if (event.data) {
      data = event.data.json() as typeof data;
    }
  } catch {
    const text = event.data?.text();
    if (text) data = { body: text };
  }

  const title = data.title || "Saheh";
  const options: NotificationOptions = {
    body: data.body || "",
    icon: "/pwa-192.png",
    badge: "/pwa-192.png",
    vibrate: [200, 100, 200, 100, 200],
    tag: data.tag || "saheh-default",
    renotify: true,
    data: { url: data.url || "/" },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener("notificationclick", (event: NotificationEvent) => {
  event.notification.close();
  const url =
    (event.notification.data as { url?: string } | undefined)?.url ||
    (event.notification.data as string | undefined) ||
    "/";

  event.waitUntil(
    self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ("focus" in client) {
          return (client as WindowClient).focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});
