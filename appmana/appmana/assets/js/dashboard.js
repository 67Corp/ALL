// ============================================================
// dashboard.js — Chart.js performances
// ============================================================
(async function () {

    const r    = await fetch(APP_URL + '/api/stats.php');
    const data = await r.json();
    if (!data.success) return;

    const { progression, by_theme, leaderboard, personal, challenges } = data.data;

    // ── Stats globales ────────────────────────────────────────
    document.getElementById('statTotalVal').textContent = personal?.total_quizzes || 0;
    document.getElementById('statAvgVal').textContent   = (personal?.avg_score || 0) + '%';
    document.getElementById('statBestVal').textContent  = (personal?.best_score || 0) + '%';
    document.getElementById('statWinsVal').textContent  = challenges?.won || 0;

    // ── Chart Progression (ligne) ─────────────────────────────
    const progCtx = document.getElementById('chartProgression');
    if (progCtx && progression?.length) {
        new Chart(progCtx, {
            type: 'line',
            data: {
                labels: progression.map(p => p.day),
                datasets: [{
                    label: 'Score moyen (%)',
                    data: progression.map(p => p.avg_score),
                    borderColor: '#e94560',
                    backgroundColor: 'rgba(233,69,96,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#e94560',
                    pointRadius: 4,
                }],
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
                    x: { ticks: { maxRotation: 45 } },
                },
            },
        });
    } else if (progCtx) {
        progCtx.parentElement.innerHTML += '<p style="color:#aaa;text-align:center;padding:20px">Aucune donnée pour l\'instant.</p>';
    }

    // ── Chart par thème (doughnut) ────────────────────────────
    const themeCtx = document.getElementById('chartTheme');
    if (themeCtx && by_theme?.length) {
        new Chart(themeCtx, {
            type: 'doughnut',
            data: {
                labels: by_theme.map(t => t.theme),
                datasets: [{
                    data: by_theme.map(t => t.avg_score),
                    backgroundColor: by_theme.map(t => t.color_hex || '#e94560'),
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.raw + '%' } },
                },
            },
        });
    }

    // ── Leaderboard ───────────────────────────────────────────
    const lbEl = document.getElementById('leaderboard');
    if (lbEl && leaderboard?.length) {
        const rankClass = (i) => i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
        const rankIcon  = (i) => i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : i + 1;

        lbEl.innerHTML = leaderboard.map((u, i) => `
            <div class="leaderboard-row">
                <div class="leaderboard-rank ${rankClass(i)}">${rankIcon(i)}</div>
                <img src="${APP_URL}/assets/images/avatar.png" alt="" class="leaderboard-avatar">
                <span class="leaderboard-name">${escHtml(u.display_name)}</span>
                <span class="leaderboard-score">${u.avg_score}%</span>
                <span style="color:#aaa;font-size:.82rem">${u.quiz_count} quiz</span>
            </div>
        `).join('');
    } else if (lbEl) {
        lbEl.innerHTML = '<p style="color:#888;padding:20px;text-align:center">Aucune donnée de classement.</p>';
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})();
