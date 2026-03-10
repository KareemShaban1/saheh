<?php
// scripts/generate_teeth_svgs.php
// Run: php scripts/generate_teeth_svgs.php
// Creates public/images/teeth/tooth_1.svg ... tooth_32.svg
$baseDir = __DIR__ . '/public/images/teeth';
if (!file_exists($baseDir)) mkdir($baseDir, 0755, true);

// three templates
$realistic = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 140" aria-hidden="true">
  <defs>
    <linearGradient id="g-real" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0%" stop-color="#ffffff"/>
      <stop offset="100%" stop-color="#f3f4f6"/>
    </linearGradient>
    <filter id="s" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="#000" flood-opacity="0.06" />
    </filter>
  </defs>
  <g filter="url(#s)">
    <path id="tooth-shape" fill="url(#g-real)" stroke="#e5e7eb" stroke-width="1" d="M60,8 C48,8 34,18 28,34 C22,50 20,70 24,86 C28,102 38,122 60,132 C82,122 92,102 96,86 C100,70 98,50 92,34 C86,18 72,8 60,8 Z"/>
  </g>
</svg>
SVG;

$schematic = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" aria-hidden="true">
  <rect width="100" height="100" rx="12" fill="#ffffff" />
  <path id="tooth-shape" d="M50,10 C42,10 30,22 26,36 C22,50 22,66 30,80 C38,94 62,94 70,80 C78,66 78,50 74,36 C70,22 58,10 50,10 Z"
        fill="#f3f4f6" stroke="#e6e7ea" stroke-width="1"/>
</svg>
SVG;

$interactive = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 140" aria-hidden="true">
  <g role="img" aria-label="Tooth">
    <path id="tooth-path" class="tooth-fill" d="M60,8 C48,8 34,18 28,34 C22,50 20,70 24,86 C28,102 38,122 60,132 C82,122 92,102 96,86 C100,70 98,50 92,34 C86,18 72,8 60,8 Z" fill="#ffffff" stroke="#cbd5e1" stroke-width="1"/>
    <circle class="surface occlusal" cx="60" cy="58" r="6" fill="transparent" />
    <g class="icon" transform="translate(78,6)" opacity="0.9">
      <rect x="0" y="0" width="28" height="18" rx="4" fill="#ffffff"/>
      <text x="14" y="12" font-size="10" text-anchor="middle" fill="#374151">#</text>
    </g>
  </g>
</svg>
SVG;

for ($i = 1; $i <= 32; $i++) {
    // Option naming: tooth_{n}_{style}.svg
    file_put_contents("$baseDir/tooth_{$i}_realistic.svg", $realistic);
    file_put_contents("$baseDir/tooth_{$i}_schematic.svg", $schematic);
    file_put_contents("$baseDir/tooth_{$i}_interactive.svg", $interactive);
}

echo "Created 96 SVGs in $baseDir (3 styles x 32 teeth)\n";
