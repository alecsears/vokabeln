/* stats.js – Chart.js initialisation */
document.addEventListener('DOMContentLoaded', () => {

    // ── Daily history bar chart ──
    const historyEl = document.getElementById('chart-history');
    if (historyEl && window.STATS_HISTORY) {
        const history = window.STATS_HISTORY;
        const labels  = history.map(d => d.date ? d.date.slice(5) : '');
        const corrects = history.map(d => d.correct  || 0);
        const wrongs   = history.map(d => d.wrong    || 0);

        new Chart(historyEl, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Richtig', data: corrects, backgroundColor: 'rgba(16,185,129,0.8)'  },
                    { label: 'Falsch',  data: wrongs,   backgroundColor: 'rgba(239, 68, 68,0.7)' }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
            }
        });
    }

    // ── Box distribution donut chart ──
    const boxEl = document.getElementById('chart-boxes');
    if (boxEl && window.STATS_BOXES) {
        const boxes = window.STATS_BOXES;
        new Chart(boxEl, {
            type: 'doughnut',
            data: {
                labels: ['Box 1', 'Box 2', 'Box 3'],
                datasets: [{
                    data: [boxes[1] || 0, boxes[2] || 0, boxes[3] || 0],
                    backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                cutout: '60%'
            }
        });
    }

    // ── Progress bar ──
    const fill = document.getElementById('progress-fill');
    if (fill && window.STATS_PROGRESS !== undefined) {
        fill.style.width = Math.min(100, window.STATS_PROGRESS) + '%';
    }
});
