<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Haykal Starter</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #fafafa;
            --fg: #18181b;
            --muted: #52525b;
            --subtle: #71717a;
            --surface: #ffffff;
            --border: #e4e4e7;
            --border-strong: #a1a1aa;
            --arrow: #a1a1aa;
            --arrow-hover: #18181b;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #09090b;
                --fg: #fafafa;
                --muted: #a1a1aa;
                --subtle: #71717a;
                --surface: #18181b;
                --border: #27272a;
                --border-strong: #3f3f46;
                --arrow: #52525b;
                --arrow-hover: #fafafa;
            }
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, sans-serif;
            background: var(--bg);
            color: var(--fg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 1.5rem;
        }
        .container { width: 100%; max-width: 720px; }
        .badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.02em;
            padding: 0.25rem 0.625rem;
            border: 1px solid var(--border);
            border-radius: 9999px;
            background: var(--surface);
            color: var(--muted);
            margin-bottom: 1.5rem;
        }
        h1 {
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin: 0 0 0.75rem;
            line-height: 1.1;
        }
        p.lead {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--muted);
            margin: 0 0 2.5rem;
            max-width: 560px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        @media (max-width: 600px) {
            .grid { grid-template-columns: 1fr; }
            h1 { font-size: 1.875rem; }
        }
        .card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.125rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.625rem;
            text-decoration: none;
            color: inherit;
            transition: border-color 120ms ease, transform 120ms ease;
        }
        .card:hover { border-color: var(--border-strong); }
        .card-text { display: flex; flex-direction: column; gap: 0.125rem; min-width: 0; }
        .card-title { font-weight: 600; font-size: 0.9375rem; }
        .card-desc { font-size: 0.8125rem; color: var(--subtle); }
        .arrow {
            color: var(--arrow);
            flex-shrink: 0;
            transition: color 120ms ease, transform 120ms ease;
        }
        .card:hover .arrow { color: var(--arrow-hover); transform: translateX(2px); }
        footer {
            padding: 1.5rem;
            text-align: center;
            font-size: 0.8125rem;
        }
        .meta { color: var(--subtle); }
        .meta a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <div class="container">
            <span class="badge">Haykal Starter</span>
            <h1>A Laravel starter, pre-wired for Haykal.</h1>
            <p class="lead">
                Authentication, multi-tenancy, permissions, REST API conventions, and Filament panels — all
                stitched together so you can skip the plumbing and start building. Replace this page with your
                app's entry point when you're ready.
            </p>

            <div class="grid">
                <a class="card" href="https://github.com/hitaqnia/haykal" target="_blank" rel="noopener">
                    <div class="card-text">
                        <span class="card-title">haykal</span>
                        <span class="card-desc">Foundation & conventions</span>
                    </div>
                    <svg class="arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>

                <a class="card" href="https://github.com/hitaqnia/haykal-core" target="_blank" rel="noopener">
                    <div class="card-text">
                        <span class="card-title">haykal-core</span>
                        <span class="card-desc">Tenancy, auth & permissions</span>
                    </div>
                    <svg class="arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>

                <a class="card" href="https://github.com/hitaqnia/haykal-api" target="_blank" rel="noopener">
                    <div class="card-text">
                        <span class="card-title">haykal-api</span>
                        <span class="card-desc">REST API building blocks</span>
                    </div>
                    <svg class="arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>

                <a class="card" href="https://github.com/hitaqnia/haykal-filament" target="_blank" rel="noopener">
                    <div class="card-text">
                        <span class="card-title">haykal-filament</span>
                        <span class="card-desc">Filament panel integrations</span>
                    </div>
                    <svg class="arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </main>

    <footer>
        <span class="meta">Laravel {{ Illuminate\Foundation\Application::VERSION }} · PHP {{ PHP_VERSION }}</span>
    </footer>
</body>
</html>
