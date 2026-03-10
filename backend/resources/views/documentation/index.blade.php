<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Documentation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            line-height: 1.6;
        }
        .layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            min-width: 280px;
            background: rgba(30, 41, 59, 0.95);
            border-right: 1px solid rgba(148, 163, 184, 0.2);
            padding: 1.5rem;
            overflow-y: auto;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }
        .sidebar-header h2 {
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar-header a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.8rem;
            transition: color 0.2s;
        }
        .sidebar-header a:hover { color: #f87171; }
        .nav-list {
            list-style: none;
        }
        .nav-list a {
            display: block;
            padding: 0.5rem 0.75rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
        }
        .nav-list a:hover {
            color: #e2e8f0;
            background: rgba(59, 130, 246, 0.15);
        }
        .nav-list a.active {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.2);
        }
        .content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem 3rem 4rem;
            max-width: 900px;
        }
        .content h1 { font-size: 2rem; margin-bottom: 1rem; }
        .content h2 { font-size: 1.5rem; margin: 2rem 0 1rem; padding-top: 1rem; border-top: 1px solid rgba(148, 163, 184, 0.2); }
        .content h3 { font-size: 1.25rem; margin: 1.5rem 0 0.75rem; }
        .content h4 { font-size: 1.1rem; margin: 1.25rem 0 0.5rem; }
        .content p { margin-bottom: 1rem; }
        .content ul, .content ol { margin: 0 0 1rem 1.5rem; }
        .content li { margin-bottom: 0.25rem; }
        .content code {
            background: rgba(59, 130, 246, 0.2);
            padding: 0.2em 0.4em;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .content pre {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
        .content pre code {
            background: none;
            padding: 0;
        }
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .content th, .content td {
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 0.5rem 0.75rem;
            text-align: left;
        }
        .content th { background: rgba(59, 130, 246, 0.15); }
        .content blockquote {
            border-left: 4px solid #3b82f6;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #94a3b8;
        }
        .content a { color: #60a5fa; text-decoration: none; }
        .content a:hover { text-decoration: underline; }
        .content hr { border: none; border-top: 1px solid rgba(148, 163, 184, 0.2); margin: 2rem 0; }
        .content strong { color: #f1f5f9; }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            .layout { flex-direction: column; }
            .content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-book"></i> Documentation</h2>
                <a href="{{ route('docs.logout') }}" title="Sign out"><i class="fas fa-sign-out-alt"></i></a>
            </div>
            <ul class="nav-list">
                @foreach($files as $file)
                    <li>
                        <a href="{{ route('docs.show', $file['filename']) }}" class="{{ $file['filename'] === $currentFile ? 'active' : '' }}">
                            {{ $file['title'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>
        <main class="content">
            <div class="markdown-body">
                {!! $content !!}
            </div>
        </main>
    </div>
</body>
</html>
