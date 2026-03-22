/**
 * Foreground alerts when the notification list gains new unread rows (polling).
 * System Web Push notifications often omit vibration on desktop and use OS-controlled sound;
 * this improves feedback while the user has the app open.
 */

let lastFeedbackAt = 0;
const THROTTLE_MS = 2000;

export function triggerInAppNotificationFeedback(): void {
	const now = Date.now();
	if (now - lastFeedbackAt < THROTTLE_MS) return;
	lastFeedbackAt = now;

	try {
		if (typeof navigator !== "undefined" && typeof navigator.vibrate === "function") {
			// Longer pattern works better on many Android devices
			navigator.vibrate([280, 100, 280, 100, 450]);
		}
	} catch {
		/* ignore */
	}

	void playShortNotificationTone().catch(() => {});
}

async function playShortNotificationTone(): Promise<void> {
	if (typeof window === "undefined") return;

	const Ctor = window.AudioContext ?? (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;
	if (!Ctor) return;

	const ctx = new Ctor();
	if (ctx.state === "suspended") {
		await ctx.resume().catch(() => {});
	}

	const osc = ctx.createOscillator();
	const gain = ctx.createGain();
	osc.type = "sine";
	osc.frequency.setValueAtTime(880, ctx.currentTime);

	const t0 = ctx.currentTime;
	gain.gain.setValueAtTime(0, t0);
	gain.gain.linearRampToValueAtTime(0.1, t0 + 0.02);
	gain.gain.exponentialRampToValueAtTime(0.0008, t0 + 0.18);

	osc.connect(gain);
	gain.connect(ctx.destination);
	osc.start(t0);
	osc.stop(t0 + 0.2);

	await new Promise<void>((resolve) => {
		osc.onended = () => {
			void ctx.close().catch(() => {});
			resolve();
		};
	});
}
