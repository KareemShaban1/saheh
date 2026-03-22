import { createRoot } from "react-dom/client";
import App from "./App.tsx";
import "./index.css";
import "leaflet/dist/leaflet.css";
import { LanguageProvider } from "./contexts/LanguageContext";
import { hidePwaSplash } from "./lib/hidePwaSplash";

// Service worker is registered by vite-plugin-pwa via injectRegister: "script-defer" (see vite.config.ts)

createRoot(document.getElementById("root")!).render(
  <LanguageProvider>
    <App />
  </LanguageProvider>,
);

// Dismiss inline splash after first paint (don't wait for window "load" — it can stick if assets are slow / dev HMR)
requestAnimationFrame(() => {
	requestAnimationFrame(() => hidePwaSplash());
});
