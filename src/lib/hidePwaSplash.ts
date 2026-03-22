/**
 * Removes the inline #pwa-splash from index.html once the app shell is ready.
 * Not tied to window "load" (which can be delayed by slow assets / dev HMR).
 */
export function hidePwaSplash(): void {
	const el = document.getElementById("pwa-splash");
	if (!el || el.getAttribute("data-dismissed") === "1") return;
	el.setAttribute("data-dismissed", "1");
	try {
		sessionStorage.setItem("saheh_skip_splash", "1");
	} catch {
		/* private mode / blocked */
	}
	el.classList.add("pwa-splash--hide");
	window.setTimeout(() => {
		try {
			el.remove();
		} catch {
			/* ignore */
		}
	}, 380);
}
