<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Performance Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
            color: #111827;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08);
            padding: 1.75rem 2rem 2.5rem;
        }

        h1 {
            margin: 0 0 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.03em;
        }

        .subtitle {
            margin-bottom: 1.5rem;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            flex: 1 1 150px;
            background: #f9fafb;
            border-radius: 0.75rem;
            padding: 0.9rem 1rem;
            border: 1px solid #e5e7eb;
        }

        .summary-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            margin-bottom: 0.35rem;
        }

        .summary-value {
            font-size: 1.15rem;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        thead {
            background: #f9fafb;
        }

        th, td {
            padding: 0.65rem 0.6rem;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            background: #f9fafb;
            z-index: 1;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        tbody tr:hover {
            background: #eef2ff;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-slow {
            background: #fef2f2;
            color: #b91c1c;
        }

        .badge-medium {
            background: #fffbeb;
            color: #92400e;
        }

        .badge-fast {
            background: #ecfdf3;
            color: #15803d;
        }

        .causes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .cause-pill {
            background: #eef2ff;
            color: #3730a3;
            border-radius: 999px;
            padding: 0.1rem 0.45rem;
            font-size: 0.75rem;
        }

        .suggestions {
            margin: 0;
            padding-left: 1.1rem;
        }

        .suggestions li {
            margin-bottom: 0.2rem;
        }

        .empty {
            padding: 2rem 0;
            text-align: center;
            color: #6b7280;
        }

        .url {
            color: #2563eb;
            text-decoration: none;
            word-break: break-all;
        }

        .url:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1.25rem 1.1rem 1.5rem;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Performance Monitor</h1>
    <p class="subtitle">
        Recent "Slow page detected" entries from <code>storage/logs/performance.log</code> with automatic optimization suggestions.
    </p>

    @if ($summary)
        <div class="summary">
            <div class="summary-card">
                <div class="summary-label">Entries</div>
                <div class="summary-value">{{ $summary['entries_count'] }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Average load time</div>
                <div class="summary-value">{{ $summary['avg_load_time'] }}s</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Max load time</div>
                <div class="summary-value">{{ $summary['max_load_time'] }}s</div>
            </div>
        </div>
    @endif

    @if (empty($entries))
        <div class="empty">
            No performance entries found yet. Trigger slow pages in your app to see logged data here.
        </div>
    @else
        <table>
            <thead>
            <tr>
                <th style="min-width: 140px;">Time</th>
                <th style="min-width: 220px;">URL</th>
                <th style="min-width: 120px;">Load time</th>
                <th style="min-width: 200px;">Causes</th>
                <th style="min-width: 280px;">Suggestions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($entries as $entry)
                <tr>
                    <td>
                        <div>{{ $entry['datetime'] ?? '-' }}</div>
                        <div style="font-size: 0.75rem; color: #6b7280;">
                            {{ strtoupper($entry['env'] ?? '') }} · {{ strtoupper($entry['level'] ?? '') }}
                        </div>
                    </td>
                    <td>
                        @if (!empty($entry['url']))
                            <a href="{{ $entry['url'] }}" target="_blank" rel="noopener" class="url">
                                {{ $entry['url'] }}
                            </a>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $time = $entry['load_time'];
                        @endphp
                        @if ($time !== null)
                            @if ($time >= 5)
                                <span class="badge badge-slow">{{ $time }}s</span>
                            @elseif ($time >= 3)
                                <span class="badge badge-medium">{{ $time }}s</span>
                            @else
                                <span class="badge badge-fast">{{ $time }}s</span>
                            @endif
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td>
                        @if (!empty($entry['causes']) && is_array($entry['causes']))
                            <div class="causes">
                                @foreach ($entry['causes'] as $cause)
                                    <span class="cause-pill">{{ $cause }}</span>
                                @endforeach
                            </div>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td>
                        @if (!empty($entry['suggestions']))
                            <ul class="suggestions">
                                @foreach ($entry['suggestions'] as $suggestion)
                                    <li>{{ $suggestion }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
</body>
</html>

