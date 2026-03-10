<?php

namespace App\Http\Controllers;

class PerformanceLogController extends Controller
{
    public function index()
    {
        $path = storage_path('logs/performance.log');

        if (! file_exists($path)) {
            return view('performance-monitor', [
                'entries' => [],
                'summary' => null,
            ]);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        // Only keep the last 100 entries to avoid rendering a huge table.
        $lines = array_slice($lines, -100);

        $entries = [];
        $totalLoadTime = 0.0;
        $countWithLoadTime = 0;

        foreach ($lines as $line) {
            $parsed = $this->parseLogLine($line);

            if ($parsed) {
                $entries[] = $parsed;

                if ($parsed['load_time'] !== null) {
                    $totalLoadTime += $parsed['load_time'];
                    $countWithLoadTime++;
                }
            }
        }

        usort($entries, function (array $a, array $b) {
            return strcmp($b['datetime'] ?? '', $a['datetime'] ?? '');
        });

        $summary = null;

        if ($countWithLoadTime > 0) {
            $summary = [
                'entries_count' => count($entries),
                'avg_load_time' => round($totalLoadTime / $countWithLoadTime, 2),
                'max_load_time' => round(max(array_column($entries, 'load_time')), 2),
            ];
        }

        return view('performance-monitor', [
            'entries' => $entries,
            'summary' => $summary,
        ]);
    }

    protected function parseLogLine(string $line): ?array
    {
        // Expected format:
        // [2025-10-18 15:35:00] local.INFO: Slow page detected {"url":"...","load_time":"5.66",...}
        if (! preg_match('/^\[(.*?)\]\s+([^.]+)\.([A-Z]+):\s+(.*?)\s+(\{.*\})\s*$/', $line, $matches)) {
            return null;
        }

        $datetime = $matches[1] ?? null;
        $env = $matches[2] ?? null;
        $level = strtolower($matches[3] ?? '');
        $message = $matches[4] ?? null;
        $json = $matches[5] ?? '{}';

        $context = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($context)) {
            $context = [];
        }

        $loadTime = isset($context['load_time']) ? (float) $context['load_time'] : null;

        return [
            'datetime' => $datetime,
            'env' => $env,
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'url' => $context['url'] ?? null,
            'load_time' => $loadTime,
            'causes' => $context['causes'] ?? [],
            'suggestions' => $this->buildSuggestions($context),
        ];
    }

    protected function buildSuggestions(array $context): array
    {
        $suggestions = [];

        $loadTime = isset($context['load_time']) ? (float) $context['load_time'] : null;

        if ($loadTime !== null) {
            if ($loadTime >= 5.0) {
                $suggestions[] = 'Very slow page (>= 5s). Enable full-page caching, optimize database queries, and move heavy work to queues.';
            } elseif ($loadTime >= 3.0) {
                $suggestions[] = 'Moderate page load time (3–5s). Defer non-critical JavaScript, lazy-load images, and reduce initial payload size.';
            }
        }

        $details = $context['details'] ?? [];
        $slowResources = $details['slow_resources'] ?? [];
        $largeImages = $details['large_images'] ?? [];

        if (is_array($slowResources) && count($slowResources) > 10) {
            $suggestions[] = 'Many slow network resources. Combine and minify CSS/JS, enable browser caching, and consider serving assets from a CDN.';
        }

        $fontResources = array_filter($slowResources, function ($resource) {
            return isset($resource['name']) && strpos($resource['name'], '.ttf') !== false;
        });

        if (count($fontResources) > 0) {
            $suggestions[] = 'Slow font files detected. Preload critical fonts, use WOFF2, and limit the number of font variants.';
        }

        $tailwindCdnResources = array_filter($slowResources, function ($resource) {
            return isset($resource['name']) && strpos($resource['name'], 'cdn.tailwindcss.com') !== false;
        });

        if (count($tailwindCdnResources) > 0) {
            $suggestions[] = 'Tailwind CSS is loaded from CDN. Compile Tailwind into local CSS assets instead of loading from CDN in production.';
        }

        $debugbarResources = array_filter($slowResources, function ($resource) {
            return isset($resource['name']) && strpos($resource['name'], '_debugbar') !== false;
        });

        if (count($debugbarResources) > 0) {
            $suggestions[] = 'Laravel Debugbar assets are loaded. Disable Debugbar in production to remove extra HTTP requests and JavaScript.';
        }

        if (is_array($largeImages) && count($largeImages) > 0) {
            $suggestions[] = 'Large images detected. Compress and resize images, use responsive image sizes, and prefer modern formats like WebP.';
        }

        $causes = $context['causes'] ?? [];
        if (is_array($causes) && count($causes) > 0) {
            $suggestions[] = 'Browser-reported causes: ' . implode(', ', $causes) . '. Prioritize optimizing these resources.';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'No specific bottleneck detected from this entry. Profile backend (queries, cache, queues) and front-end bundle size for further optimizations.';
        }

        return array_values(array_unique($suggestions));
    }
}

