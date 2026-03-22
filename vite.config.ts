import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { VitePWA } from "vite-plugin-pwa";
import { componentTagger } from "lovable-tagger";

// https://vitejs.dev/config/
export default defineConfig(({ mode, command }) => ({
  server: {
    host: "::",
    port: 8080,
    hmr: {
      overlay: false,
    },
  },
  plugins: [
    // Spread PWA plugins to top level — a nested array may not register resolveId for `virtual:pwa-register`
    ...VitePWA({
      strategies: "injectManifest",
      srcDir: "src",
      filename: "sw.ts",
      registerType: "autoUpdate",
      // Dev: "inline" so registration uses HMR `{ type: swType }` (module). "script-defer" uses dev-dist/registerSW.js
      // which browsers often cache without `type: 'module'` → "Cannot use import statement outside a module" on dev-sw.js.
      injectRegister: command === "serve" ? "inline" : "script-defer",
      injectManifest: {
        globPatterns: ["**/*.{js,css,html,ico,png,svg,woff2,woff,ttf}"],
        maximumFileSizeToCacheInBytes: 6 * 1024 * 1024,
      },
      workbox: {
        maximumFileSizeToCacheInBytes: 6 * 1024 * 1024,
      },
      includeAssets: ["pwa-icon.svg", "pwa-192.png", "pwa-512.png", "pwa-splash.png"],
      manifest: {
        id: "/",
        name: "Saheh",
        short_name: "Saheh",
        description: "Healthcare platform — clinics, labs, radiology & patient care",
        theme_color: "#0d9488",
        background_color: "#f8fafc",
        display: "standalone",
        orientation: "portrait-primary",
        scope: "/",
        start_url: "/",
        lang: "en",
        categories: ["health", "medical", "lifestyle"],
        screenshots: [
          {
            src: "pwa-splash.png",
            type: "image/png",
            form_factor: "narrow",
            label: "Saheh splash screen",
          },
        ],
        icons: [
          {
            src: "pwa-192.png",
            sizes: "192x192",
            type: "image/png",
          },
          {
            src: "pwa-512.png",
            sizes: "512x512",
            type: "image/png",
          },
          {
            src: "pwa-512.png",
            sizes: "512x512",
            type: "image/png",
            purpose: "maskable",
          },
          {
            src: "pwa-icon.svg",
            sizes: "any",
            type: "image/svg+xml",
            purpose: "any",
          },
        ],
      },
      devOptions: {
        enabled: true,
        // injectManifest dev SW is ESM (workbox imports); classic SW cannot parse `import`
        type: "module",
      },
    }),
    react(),
    mode === "development" && componentTagger(),
  ].filter(Boolean),
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
}));
