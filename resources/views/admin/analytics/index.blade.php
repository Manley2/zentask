@extends('layouts.dashboard-layout')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h1 class="text-3xl lg:text-4xl font-bold text-white">Analytics</h1>
        <p class="text-blue-100/70 mt-1">Traffic pengunjung 14 hari terakhir.</p>
    </div>

    <div class="glass-card rounded-2xl p-6 border border-white/10">
        <canvas id="trafficChart" height="120"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const ctx = document.getElementById('trafficChart');
        if (!ctx) return;
        const labels = @json($labels);
        const pageViews = @json($pageViews);
        const uniqueVisitors = @json($uniqueVisitors);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Pageviews',
                        data: pageViews,
                        borderColor: 'rgba(59, 130, 246, 0.9)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Unique Visitors',
                        data: uniqueVisitors,
                        borderColor: 'rgba(34, 211, 238, 0.9)',
                        backgroundColor: 'rgba(34, 211, 238, 0.1)',
                        tension: 0.35,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#cbd5f5'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)' }
                    }
                }
            }
        });
    })();
</script>
@endsection
