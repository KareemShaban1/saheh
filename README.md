# Welcome to your Lovable project

## Project info

**URL**: https://lovable.dev/projects/REPLACE_WITH_PROJECT_ID

## Project documentation

- [Project Modules](./PROJECT_MODULES.md)
- [Organization User Flows](./ORGANIZATION_USER_FLOWS.md)

## How can I edit this code?

There are several ways of editing your application.

**Use Lovable**

Simply visit the [Lovable Project](https://lovable.dev/projects/REPLACE_WITH_PROJECT_ID) and start prompting.

Changes made via Lovable will be committed automatically to this repo.

**Use your preferred IDE**

If you want to work locally using your own IDE, you can clone this repo and push changes. Pushed changes will also be reflected in Lovable.

The only requirement is having Node.js & npm installed - [install with nvm](https://github.com/nvm-sh/nvm#installing-and-updating)

Follow these steps:

```sh
# Step 1: Clone the repository using the project's Git URL.
git clone <YOUR_GIT_URL>

# Step 2: Navigate to the project directory.
cd <YOUR_PROJECT_NAME>

# Step 3: Install the necessary dependencies.
npm i

# Step 4: Start the development server with auto-reloading and an instant preview.
npm run dev
```

**Edit a file directly in GitHub**

- Navigate to the desired file(s).
- Click the "Edit" button (pencil icon) at the top right of the file view.
- Make your changes and commit the changes.

**Use GitHub Codespaces**

- Navigate to the main page of your repository.
- Click on the "Code" button (green button) near the top right.
- Select the "Codespaces" tab.
- Click on "New codespace" to launch a new Codespace environment.
- Edit files directly within the Codespace and commit and push your changes once you're done.

## What technologies are used for this project?

This project is built with:

- Vite
- TypeScript
- React
- shadcn-ui
- **Tailwind CSS** — utility-first styling via `tailwind.config.ts`, `postcss.config.js`, and `src/index.css` (`@tailwind base/components/utilities`). Design tokens live in CSS variables in `index.css`.
- **PWA** — `vite-plugin-pwa` registers a service worker, injects a Web App Manifest (icons, theme/background colors, standalone display), and caches static assets. **Install** is offered from the public header (when the browser fires `beforeinstallprompt`) and on the home page; iOS users see an “Add to Home Screen” hint.

### Web Push (notifications when the app is closed)

The app uses **standard Web Push** (VAPID + service worker). After login, **clinic / lab / radiology dashboards**, **super admin**, and **patient** layouts register `PushManager` and save the subscription to Laravel. When a `database` notification is sent to a user who has subscribed, the backend pushes a payload; the service worker (`src/sw.ts`) shows a system notification with **vibration** (where the browser/OS supports it). **Custom notification sounds** are not fully controllable on the web—the OS usually plays its default; use a **native app** if you need branded sounds.

**Backend setup**

1. `cd backend && composer install` (includes `minishlink/web-push`).
2. `php artisan migrate` — creates `push_subscriptions`.
3. Generate VAPID keys, e.g. `npx web-push generate-vapid-keys`, and set in `backend/.env`:
   - `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` (e.g. `mailto:you@example.com`)
   - `FRONTEND_URL` — public URL of the React app (used to build links in notifications).
4. Deploy over **HTTPS** (required for push except on `localhost`).
5. After editing `.env` on the server, run **`php artisan config:clear`** (or avoid stale `config:cache`).

**VAPID troubleshooting** (`Private key should be 32 bytes long when decoded`)

- Keys must be the **Base64Url** pair from `npx web-push generate-vapid-keys` (one continuous string each). **Do not** paste PEM blocks or swap public/private.
- Put each key on **one line** in `.env`. Avoid wrapping the value in **extra** quotes; if you use quotes, ensure the key itself has no spaces or line breaks inside.
- If keys were edited with standard Base64 (`+` / `/`), the backend normalizes to Base64Url when possible—if it still fails, **regenerate** keys and update both backend `.env` and the frontend subscription flow (users may need to re-enable notifications after a key change).

**API** (prefix `api/v1`): `GET /push/vapid-public-key` (public); authenticated `POST` … `/organization/push/subscribe`, `/admin/push/subscribe`, `/patient/push/subscribe` (body = browser `subscription.toJSON()` shape).

**Production: bell updates work but no push / no `/push/vapid-public-key` in Laravel logs**

- The SPA must call the **real** API host. Set **`VITE_BASE_URL`** in the project root `.env` **before** `npm run build` (e.g. `https://api.yourdomain.com/api/v1`), or uncomment and set **`<meta name="app-api-base" ...>`** in `index.html` on the server so you don’t need a rebuild.
- If `VITE_BASE_URL` is missing, older builds pointed at `http://localhost:8000/api/v1` — the browser never hits your server, so push never registers and you only see **in-app** (database) notifications.
- Same-origin deploy: if Nginx serves the app at `https://app.example.com` and proxies **`/api`** to Laravel, the built app can use the default **`https://app.example.com/api/v1`** fallback (no `VITE_BASE_URL` needed).

**Sound & vibration**

- **Push (background):** `src/sw.ts` uses `showNotification` with `vibrate` and `silent: false`. Many **desktop** browsers still **ignore** vibration for notifications; **sound** is almost always the **OS default** (and can be muted per-site in system settings).
- **While the app is open:** the service worker **posts a message** to visible tabs so the page can run **`navigator.vibrate`** + a short **Web Audio** beep (`src/lib/notificationFeedback.ts`). The **patient** and **org dashboard** bells also **beep/vibrate** when polling sees **new unread** IDs (so you get feedback even if push UI is quiet).
- **First visit:** some browsers block audio until the user has **interacted** with the page (click/tap); vibration may still work on supported phones.

**Try it (patient push + in-app list)**

1. Log in as a **patient** in the PWA, allow notifications, open the **bell** in the patient header (loads `GET /patient/notifications`).
2. In the **clinic dashboard**, open a reservation edit URL (e.g. `/clinic-dashboard/reservations/3/edit`), change **Acceptance** to **Approved** or **Not approved**, and save.
3. The patient should get a **database notification** (bell list) and, if VAPID is set and the patient subscribed, a **system / push** notification with vibration (supported devices).

### PWA assets

- `public/pwa-icon.svg` — source vector for favicon / manifest.
- `public/pwa-192.png`, `public/pwa-512.png` — PNG icons (install / home screen).
- `public/pwa-splash.png` — portrait splash (manifest screenshot + iOS `apple-touch-startup-image`).
- **Regenerate PNGs from SVG** after editing the icon: `npm run pwa:assets` (uses `sharp`).
- `index.html` — first-paint **splash** while the bundle loads, styled with Tailwind (`@layer components` → `.pwa-splash` in `src/index.css`).
- **Install**: **Install app** in the public header, **dashboard** top bar, and **patient** sidebar (when installable); **Add to Home Screen** on iOS links to `/#pwa-install`; **`PwaInstallCard`** on the home page.

After pulling changes, run `npm install`, then `npm run pwa:assets` once if PNGs are missing, then `npm run dev` or `npm run build` (the service worker is generated on build).

If dev shows **`Cannot use import statement outside a module` for `dev-sw.js`**: the injectManifest dev worker is ESM and must be registered with `{ type: "module" }`. This repo sets `devOptions.type: "module"` and uses **`injectRegister: "inline"` while `vite` is serving** so registration goes through the PWA dev client (not a cached `dev-dist/registerSW.js`). If it still happens, unregister the old worker (Application → Service Workers) and hard-refresh.

## How can I deploy this project?

Simply open [Lovable](https://lovable.dev/projects/REPLACE_WITH_PROJECT_ID) and click on Share -> Publish.

## Can I connect a custom domain to my Lovable project?

Yes, you can!

To connect a domain, navigate to Project > Settings > Domains and click Connect Domain.

Read more here: [Setting up a custom domain](https://docs.lovable.dev/features/custom-domain#custom-domain)
