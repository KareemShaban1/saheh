/**
 * Generates PNG PWA assets from public/pwa-icon.svg.
 * Run: npm run pwa:assets  (requires devDependency `sharp`)
 */
import sharp from "sharp";
import path from "path";
import { fileURLToPath } from "url";
import fs from "fs";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, "..");
const pub = path.join(root, "public");
const svgPath = path.join(pub, "pwa-icon.svg");

if (!fs.existsSync(svgPath)) {
  console.error("Missing", svgPath);
  process.exit(1);
}

const bg = { r: 248, g: 250, b: 252, alpha: 1 }; // matches manifest background_color #f8fafc

async function main() {
  const base = sharp(svgPath, { density: 300 });

  await base.clone().resize(192, 192).png().toFile(path.join(pub, "pwa-192.png"));
  console.log("Wrote public/pwa-192.png");

  await base.clone().resize(512, 512).png().toFile(path.join(pub, "pwa-512.png"));
  console.log("Wrote public/pwa-512.png");

  // Portrait splash (iOS / manifest screenshot) — light background + centered logo
  const W = 1242;
  const H = 2688;
  const logoSize = 320;
  const iconBuf = await sharp(svgPath, { density: 300 }).resize(logoSize, logoSize).png().toBuffer();
  const left = Math.floor((W - logoSize) / 2);
  const top = Math.floor((H - logoSize) / 2 - 120);

  await sharp({
    create: { width: W, height: H, channels: 4, background: bg },
  })
    .composite([{ input: iconBuf, left, top }])
    .png()
    .toFile(path.join(pub, "pwa-splash.png"));

  console.log("Wrote public/pwa-splash.png");
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
