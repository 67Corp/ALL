// ============================================================
// search.js — Recherche prédictive + page questions
// ============================================================
(function () {

    // ── Navbar search (dropdown) ──────────────────────────────
    const navSearch = document.getElementById('navSearch');
    const dropdown  = document.getElementById('searchDropdown');
    let   navTimer  = null;

    if (navSearch && dropdown) {
        const parent = navSearch.closest('.navbar-search');
        if (parent) parent.style.position = 'relative';

        navSearch.addEventListener('input', () => {
            clearTimeout(navTimer);
            const q = navSearch.value.trim();
            if (q.length < 2) { dropdown.classList.add('hidden'); return; }
            navTimer = setTimeout(() => fetchNavSuggestions(q), 300);
        });

        navSearch.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                dropdown.classList.add('hidden');
                window.location.href = APP_URL + '/pages/questions.php?q=' + encodeURIComponent(navSearch.value.trim());
            }
        });

        document.addEventListener('click', (e) => {
            if (!navSearch.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    async function fetchNavSuggestions(q) {
        try {
            const r    = await fetch(APP_URL + '/api/search.php?q=' + encodeURIComponent(q) + '&limit=5');
            const data = await r.json();
            if (!data.success) return;

            const items = data.data.db || [];
            if (!items.length) { dropdown.classList.add('hidden'); return; }

            dropdown.innerHTML = items.map(it =>
                `<div class="search-dropdown-item" data-type="${it.type}" data-id="${it.id}" onclick="location.href=APP_URL+'/pages/questions.php?q=${encodeURIComponent(it.title)}'">
                    <strong>${escHtml(it.title)}</strong>
                    <small style="color:#666;display:block">${it.theme || 'Cours'}</small>
                </div>`
            ).join('');
            dropdown.classList.remove('hidden');
        } catch (e) { /* silencieux */ }
    }

    // ── Page accueil (hero search) ────────────────────────────
    const heroInput   = document.getElementById('heroInput');
    const heroBtn     = document.getElementById('heroBtn');
    const heroResults = document.getElementById('heroResults');

    if (heroInput) {
        heroInput.addEventListener('input', () => {
            heroInput.style.height = 'auto';
            heroInput.style.height = heroInput.scrollHeight + 'px';
        });

        heroInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doHeroSearch(); }
        });
    }

    if (heroBtn) heroBtn.addEventListener('click', doHeroSearch);

    async function doHeroSearch() {
        const q = heroInput?.value.trim();
        if (!q) return;

        heroResults?.classList.remove('hidden');
        heroResults.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        try {
            const r    = await fetch(APP_URL + '/api/search.php?q=' + encodeURIComponent(q) + '&ai=1');
            const data = await r.json();
            if (!data.success) { heroResults.innerHTML = '<p style="color:#e94560;text-align:center">Erreur de recherche.</p>'; return; }

            let html = '';

            if (data.data.db?.length) {
                html += '<h4 style="color:#8888aa;font-size:.85rem;margin-bottom:8px">Dans vos cours</h4>';
                html += data.data.db.map(it =>
                    `<div class="hero-result-card" onclick="location.href='${APP_URL}/pages/cours.php'">
                        <h4>${escHtml(it.title)}</h4>
                        <p>${escHtml(it.excerpt || '')}</p>
                    </div>`
                ).join('');
            }

            if (data.data.ai) {
                html += '<h4 style="color:#4ecdc4;font-size:.85rem;margin:14px 0 8px">Réponse IA</h4>';
                html += `<div class="hero-ai-answer">${escHtml(data.data.ai)}</div>`;
            }

            if (!html) html = '<p style="color:#8888aa;text-align:center">Aucun résultat. Essayez un autre terme.</p>';
            heroResults.innerHTML = html;
        } catch (e) {
            heroResults.innerHTML = '<p style="color:#e94560;text-align:center">Erreur réseau.</p>';
        }
    }

    // ── Page questions.php ────────────────────────────────────
    const questionInput  = document.getElementById('questionInput');
    const btnSearch      = document.getElementById('btnSearch');
    const useAI          = document.getElementById('useAI');
    const resultsContainer = document.getElementById('resultsContainer');
    const dbResultsDiv   = document.getElementById('dbResults');
    const aiResultDiv    = document.getElementById('aiResult');
    const dbCountBadge   = document.getElementById('dbCount');
    const searchLoading  = document.getElementById('searchLoading');

    if (btnSearch) btnSearch.addEventListener('click', doPageSearch);

    if (questionInput) {
        questionInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); doPageSearch(); }
        });

        // Pré-remplir depuis URL ?q=
        const params = new URLSearchParams(window.location.search);
        if (params.get('q')) {
            questionInput.value = params.get('q');
            doPageSearch();
        }
    }

    async function doPageSearch() {
        const q   = questionInput?.value.trim();
        if (!q) return;

        resultsContainer?.classList.add('hidden');
        searchLoading?.classList.remove('hidden');

        const ai = useAI?.checked ? '1' : '0';

        try {
            const r    = await fetch(APP_URL + '/api/search.php?q=' + encodeURIComponent(q) + '&ai=' + ai);
            const data = await r.json();

            searchLoading?.classList.add('hidden');
            resultsContainer?.classList.remove('hidden');

            // DB results
            const dbItems = data.data?.db || [];
            if (dbCountBadge) dbCountBadge.textContent = dbItems.length;

            if (dbResultsDiv) {
                dbResultsDiv.innerHTML = dbItems.length
                    ? dbItems.map(it => `
                        <div class="result-card">
                            <h4>${escHtml(it.title)}</h4>
                            <p>${escHtml(it.excerpt || '')}</p>
                            <small style="color:#aaa">${it.theme || ''} · ${it.type === 'course' ? 'Cours' : 'Résumé'}</small>
                        </div>`).join('')
                    : '<p style="color:#aaa">Aucun résultat dans vos cours.</p>';
            }

            // AI answer
            if (aiResultDiv) {
                aiResultDiv.textContent = data.data?.ai
                    ? data.data.ai
                    : '(Recherche IA désactivée ou limite atteinte)';
            }

        } catch (e) {
            searchLoading?.classList.add('hidden');
        }
    }

    // ── Utilitaire ────────────────────────────────────────────
    function escHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Exposer pour les chips de suggestion
    window.fillSearch = function (chip) {
        if (heroInput) {
            heroInput.value = chip.textContent;
            heroInput.style.height = 'auto';
            heroInput.style.height = heroInput.scrollHeight + 'px';
            heroInput.focus();
        }
    };

})();
