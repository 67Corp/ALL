// ============================================================
// summarizer.js — Résumé URL + gestion des cours
// ============================================================
(function () {

    // ── Gestion de modale ────────────────────────────────────
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.modal;
            document.getElementById(id)?.classList.add('hidden');
        });
    });

    document.getElementById('btnNewCourse')?.addEventListener('click', () => {
        document.getElementById('modalNewCourse')?.classList.remove('hidden');
    });

    // Onglets de la modale
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.dataset.tab;
            const parent = btn.closest('.modal') || btn.closest('.content');
            parent?.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            parent?.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(tabId)?.classList.add('active');
        });
    });

    // ── Créer un cours manuellement ──────────────────────────
    document.getElementById('btnSaveCourse')?.addEventListener('click', async () => {
        const title    = document.getElementById('courseTitle')?.value.trim();
        const themeId  = document.getElementById('courseTheme')?.value;
        const desc     = document.getElementById('courseDesc')?.value.trim();
        const isPublic = document.getElementById('coursePublic')?.checked ? 1 : 0;

        if (!title || !themeId) { alert('Titre et thème requis.'); return; }

        const r    = await fetch(APP_URL + '/api/courses.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, theme_id: themeId, description: desc, is_public: isPublic, csrf_token: CSRF_TOKEN }),
        });
        const data = await r.json();
        if (data.success) {
            document.getElementById('modalNewCourse')?.classList.add('hidden');
            loadCourses();
        } else {
            alert(data.error || 'Erreur lors de la création.');
        }
    });

    // ── Résumé depuis URL ────────────────────────────────────
    const btnSummarize = document.getElementById('btnSummarize');
    const summaryResult = document.getElementById('summaryResult');
    const summaryText   = document.getElementById('summaryText');

    btnSummarize?.addEventListener('click', async () => {
        const url = document.getElementById('summaryUrl')?.value.trim();
        if (!url) { alert('Entrez une URL valide.'); return; }

        btnSummarize.textContent = '⚡ Analyse en cours...';
        btnSummarize.disabled = true;

        try {
            const r    = await fetch(APP_URL + '/api/summarize.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url }),
            });
            const data = await r.json();

            if (data.success) {
                summaryText.textContent = data.data.summary;
                summaryResult?.classList.remove('hidden');
                await loadUserCourses();
            } else {
                alert(data.error || 'Erreur lors de l\'analyse.');
            }
        } catch (e) {
            alert('Erreur réseau.');
        } finally {
            btnSummarize.textContent = 'Analyser avec Gemini ⚡';
            btnSummarize.disabled = false;
        }
    });

    // Sélectionner tout
    document.getElementById('btnSelectAll')?.addEventListener('click', () => {
        const sel = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(summaryText);
        sel.removeAllRanges();
        sel.addRange(range);
    });

    // Choix cours existant / nouveau
    document.getElementById('summaryCourse')?.addEventListener('change', function () {
        const newFields = document.getElementById('newCourseFields');
        newFields.style.display = this.value === 'new' ? 'block' : 'none';
    });

    // Sauvegarder le résumé
    document.getElementById('btnSaveSummary')?.addEventListener('click', async () => {
        const courseSelect  = document.getElementById('summaryCourse');
        const rawContent    = summaryText?.textContent.trim();
        const sourceUrl     = document.getElementById('summaryUrl')?.value.trim();

        if (!rawContent) { alert('Aucun contenu à sauvegarder.'); return; }

        let courseId = parseInt(courseSelect?.value);
        let summaryTitle = document.getElementById('summaryTitle')?.value.trim();

        // Créer un nouveau cours si nécessaire
        if (!courseId || isNaN(courseId)) {
            const title   = summaryTitle || 'Cours sans titre';
            const themeId = document.getElementById('summaryTheme')?.value;
            const r       = await fetch(APP_URL + '/api/courses.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, theme_id: themeId, csrf_token: CSRF_TOKEN }),
            });
            const d = await r.json();
            if (!d.success) { alert(d.error || 'Erreur de création du cours.'); return; }
            courseId = d.data.id;
        }

        // Récupérer les parties sélectionnées
        const selection = window.getSelection();
        const savedParts = (selection && !selection.isCollapsed)
            ? [selection.toString().trim()]
            : null;

        const r2 = await fetch(APP_URL + '/api/courses.php?action=save_summary', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                course_id: courseId,
                title: summaryTitle || null,
                raw_content: rawContent,
                saved_parts: savedParts,
                source_url: sourceUrl,
                csrf_token: CSRF_TOKEN,
            }),
        });
        const d2 = await r2.json();
        if (d2.success) {
            alert('Résumé sauvegardé avec succès !');
            document.getElementById('modalNewCourse')?.classList.add('hidden');
            loadCourses();
        } else {
            alert(d2.error || 'Erreur de sauvegarde.');
        }
    });

    // ── Charger les cours ────────────────────────────────────
    let currentPage  = 1;
    let currentTheme = '';

    async function loadCourses(page = 1, themeId = '') {
        const grid = document.getElementById('coursesGrid');
        const pag  = document.getElementById('coursesPagination');
        if (!grid) return;

        grid.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        const r    = await fetch(APP_URL + `/api/courses.php?action=list&page=${page}&theme_id=${themeId}`);
        const data = await r.json();
        if (!data.success) { grid.innerHTML = '<p>Erreur de chargement.</p>'; return; }

        const { courses, pagination } = data.data;

        if (!courses.length) {
            grid.innerHTML = '<div class="loading-spinner">Aucun cours. Créez votre premier cours !</div>';
            if (pag) pag.innerHTML = '';
            return;
        }

        grid.innerHTML = courses.map(c => `
            <div class="course-card" onclick="location.href='${APP_URL}/pages/cours.php?id=${c.id}'">
                <div class="course-card-header">
                    <span class="badge" style="background:${c.color_hex || '#e94560'}">${escHtml(c.theme_name || 'Thème')}</span>
                    ${c.is_public ? '<small style="color:#4ecdc4">Public</small>' : ''}
                </div>
                <h3>${escHtml(c.title)}</h3>
                <p>${escHtml(c.description ? c.description.slice(0, 100) + '…' : 'Aucune description.')}</p>
                <div class="course-card-footer">
                    <span>${c.summary_count} résumé${c.summary_count > 1 ? 's' : ''}</span>
                    <span>${formatDate(c.created_at)}</span>
                </div>
            </div>
        `).join('');

        // Pagination
        if (pag && pagination.total_pages > 1) {
            let pHtml = '';
            for (let i = 1; i <= pagination.total_pages; i++) {
                pHtml += `<button class="page-btn ${i === pagination.current ? 'active' : ''}" onclick="window._loadPage(${i})">${i}</button>`;
            }
            pag.innerHTML = pHtml;
        } else if (pag) pag.innerHTML = '';
    }

    window._loadPage = function (page) { loadCourses(page, currentTheme); };

    // Filtres thème
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTheme = btn.dataset.theme || '';
            currentPage  = 1;
            loadCourses(1, currentTheme);
        });
    });

    // Charger les cours de l'utilisateur dans le select
    async function loadUserCourses() {
        const select = document.getElementById('summaryCourse');
        if (!select) return;
        const r = await fetch(APP_URL + '/api/courses.php?action=list');
        const d = await r.json();
        if (!d.success) return;
        const options = d.data.courses.map(c => `<option value="${c.id}">${escHtml(c.title)}</option>`).join('');
        select.innerHTML = '<option value="new">+ Créer un nouveau cours</option>' + options;
    }

    // Init
    if (document.getElementById('coursesGrid')) loadCourses();

    // Utils
    function escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatDate(str) {
        if (!str) return '';
        const d = new Date(str);
        return d.toLocaleDateString('fr-FR');
    }

})();
