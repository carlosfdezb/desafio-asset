<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            background: #0a0a0a;
            color: #EDEDEC;
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 1.75rem; font-weight: 600; margin-bottom: 1.5rem; }
        .search-form {
            display: flex;
            gap: .75rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .search-form input {
            padding: .6rem 1rem;
            border-radius: .375rem;
            border: 1px solid #3E3E3A;
            background: #161615;
            color: #EDEDEC;
            font-size: .875rem;
            flex: 1;
            min-width: 140px;
        }
        .search-form input::placeholder { color: #706f6c; }
        .search-form button {
            padding: .6rem 1.5rem;
            border-radius: .375rem;
            border: 1px solid #3E3E3A;
            background: #EDEDEC;
            color: #1b1b18;
            font-weight: 500;
            font-size: .875rem;
            cursor: pointer;
            transition: background .15s;
        }
        .search-form button:hover { background: #fff; }
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background: #161615;
            border: 1px solid #3E3E3A;
            border-radius: .5rem;
        }
        .stats-header .slug-info h2 { font-size: 1.125rem; font-weight: 600; }
        .stats-header .slug-info a { color: #A1A09A; font-size: .8rem; word-break: break-all; }
        .stats-header .total-clicks {
            font-size: 2rem;
            font-weight: 600;
            text-align: right;
        }
        .stats-header .total-clicks span { display: block; font-size: .75rem; color: #A1A09A; font-weight: 400; }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 768px) {
            .charts-grid { grid-template-columns: 1fr 1fr; }
            .chart-card.full-width { grid-column: 1 / -1; }
        }
        .chart-card {
            background: #161615;
            border: 1px solid #3E3E3A;
            border-radius: .5rem;
            padding: 1.25rem;
        }
        .chart-card h3 {
            font-size: .9rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: #A1A09A;
        }
        .message {
            text-align: center;
            color: #A1A09A;
            padding: 4rem 1rem;
            font-size: .95rem;
        }
        .message.error { color: #F53003; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard de Estadísticas</h1>

        <form class="search-form" id="searchForm">
            <input type="text" id="slugInput" placeholder="Ingresa el slug..." required>
            <input type="text" id="apiKeyInput" placeholder="API Key (opcional)">
            <button type="submit">Buscar</button>
        </form>

        <div id="placeholder" class="message">
            Ingresa un slug para ver sus estadísticas.
        </div>

        <div id="errorMessage" class="message error hidden"></div>

        <div id="statsContent" class="hidden">
            <div class="stats-header">
                <div class="slug-info">
                    <h2 id="slugName"></h2>
                    <a id="originalUrl" href="#" target="_blank" rel="noopener noreferrer"></a>
                </div>
                <div class="total-clicks">
                    <span>Total de clics</span>
                    <span id="totalClicks">0</span>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-card full-width">
                    <h3>Clics por Día (últimos 7 días)</h3>
                    <canvas id="clicksPerDayChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Top Referers</h3>
                    <canvas id="topReferersChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Clics por País</h3>
                    <canvas id="clicksByCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const charts = {};

        document.getElementById('searchForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const slug = document.getElementById('slugInput').value.trim();
            const apiKey = document.getElementById('apiKeyInput').value.trim();

            if (!slug) return;

            document.getElementById('placeholder').classList.add('hidden');
            document.getElementById('errorMessage').classList.add('hidden');
            document.getElementById('statsContent').classList.add('hidden');

            try {
                const headers = { 'Accept': 'application/json' };
                if (apiKey) headers['X-API-Key'] = apiKey;

                const res = await fetch(`/api/stats/${encodeURIComponent(slug)}`, { headers });
                const data = await res.json();

                if (!res.ok) {
                    const errEl = document.getElementById('errorMessage');
                    errEl.textContent = data.message || `Error ${res.status}`;
                    errEl.classList.remove('hidden');
                    return;
                }

                renderStats(data);
            } catch (err) {
                const errEl = document.getElementById('errorMessage');
                errEl.textContent = 'Error de conexión al servidor.';
                errEl.classList.remove('hidden');
            }
        });

        function renderStats(data) {
            document.getElementById('slugName').textContent = data.slug;
            document.getElementById('originalUrl').textContent = data.original_url;
            document.getElementById('originalUrl').href = data.original_url;
            document.getElementById('totalClicks').textContent = data.total_clicks.toLocaleString();
            document.getElementById('statsContent').classList.remove('hidden');

            renderClicksPerDay(data.clicks_per_day);
            renderTopReferers(data.top_referers);
            renderClicksByCountry(data.clicks_by_country);
        }

        function destroyChart(name) {
            if (charts[name]) { charts[name].destroy(); charts[name] = null; }
        }

        function renderClicksPerDay(clicksPerDay) {
            destroyChart('clicksPerDay');
            const labels = clicksPerDay.map(i => i.date).reverse();
            const values = clicksPerDay.map(i => i.clicks).reverse();

            charts.clicksPerDay = new Chart(document.getElementById('clicksPerDayChart'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Clics',
                        data: values,
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: 'rgba(99, 102, 241, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#A1A09A' }, grid: { color: '#3E3E3A' } },
                        y: { beginAtZero: true, ticks: { color: '#A1A09A', precision: 0 }, grid: { color: '#3E3E3A' } }
                    }
                }
            });
        }

        function renderTopReferers(topReferers) {
            destroyChart('topReferers');
            const labels = topReferers.map(i => i.referer);
            const values = topReferers.map(i => i.clicks);
            const colors = ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe'];

            charts.topReferers = new Chart(document.getElementById('topReferersChart'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, values.length),
                        borderColor: '#161615',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#A1A09A', padding: 12, font: { size: 11 } } }
                    }
                }
            });
        }

        function renderClicksByCountry(clicksByCountry) {
            destroyChart('clicksByCountry');
            const labels = clicksByCountry.map(i => i.country);
            const values = clicksByCountry.map(i => i.clicks);
            const colors = ['#10b981', '#34d399', '#6ee7b7', '#a7f3d0', '#d1fae5', '#059669', '#047857', '#065f46', '#064e3b', '#022c22'];

            charts.clicksByCountry = new Chart(document.getElementById('clicksByCountryChart'), {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, values.length),
                        borderColor: '#161615',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#A1A09A', padding: 12, font: { size: 11 } } }
                    }
                }
            });
        }
    </script>
</body>
</html>
