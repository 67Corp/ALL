// ============================================================
// quiz.js — Quizz & Défis
// ============================================================
(function () {

    const isPlayPage = typeof QUIZ_ID !== 'undefined';

    // ============================================================
    // PAGE QUIZ.PHP — liste + défis
    // ============================================================
    if (!isPlayPage) {

        // Onglets
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.dataset.tab;
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(tabId)?.classList.add('active');
            });
        });

        // Charger les quiz
        async function loadQuizzes() {
            const grid = document.getElementById('quizGrid');
            if (!grid) return;
            const r = await fetch(APP_URL + '/api/quiz.php?action=list');
            const d = await r.json();
            if (!d.success) { grid.innerHTML = '<p>Erreur.</p>'; return; }

            const quizzes = d.data;
            if (!quizzes.length) {
                grid.innerHTML = '<div class="loading-spinner">Aucun quiz. Générez-en depuis un cours !</div>';
                return;
            }

            grid.innerHTML = quizzes.map(q => `
                <div class="quiz-card">
                    <span class="badge" style="background:${q.color_hex || '#e94560'};margin-bottom:8px">${escHtml(q.theme_name || '')}</span>
                    <h3>${escHtml(q.title)}</h3>
                    <p>${q.num_questions} questions · Cours: ${escHtml(q.course_title)}</p>
                    <div class="quiz-card-footer">
                        <span style="color:#888;font-size:.8rem">
                            ${q.attempt_count > 0 ? 'Meilleur: ' + q.best_score + '%' : 'Jamais fait'}
                        </span>
                        <a href="${APP_URL}/pages/quizz_play.php?quiz_id=${q.id}" class="btn-primary btn-sm">
                            ${q.attempt_count > 0 ? 'Refaire' : 'Commencer'}
                        </a>
                    </div>
                </div>
            `).join('');
        }

        // Charger l'historique
        async function loadHistory() {
            const tbody = document.getElementById('historyBody');
            if (!tbody) return;
            const r = await fetch(APP_URL + '/api/quiz.php?action=history');
            const d = await r.json();
            if (!d.success || !d.data?.length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#888">Aucun historique.</td></tr>';
                return;
            }
            tbody.innerHTML = d.data.map(s => `
                <tr>
                    <td>${escHtml(s.quiz_title)}</td>
                    <td><strong style="color:${s.score >= 80 ? '#4ecdc4' : s.score >= 50 ? '#f9ca24' : '#e94560'}">${s.score}%</strong></td>
                    <td>${new Date(s.finished_at).toLocaleDateString('fr-FR')}</td>
                    <td>${s.challenge_id ? '⚔️ Défi' : 'Solo'}</td>
                </tr>
            `).join('');
        }

        // Charger les défis
        async function loadChallenges() {
            const list = document.getElementById('challengesList');
            if (!list) return;
            const r = await fetch(APP_URL + '/api/challenge.php?action=list');
            const d = await r.json();
            if (!d.success || !d.data?.length) {
                list.innerHTML = '<div class="loading-spinner">Aucun défi.</div>';
                return;
            }
            list.innerHTML = d.data.map(ch => `
                <div class="challenge-card">
                    <img src="${APP_URL}/assets/images/avatar.png" alt="" class="challenge-avatar">
                    <div class="challenge-info">
                        <strong>${escHtml(ch.challenger_name)}</strong> vs <strong>${escHtml(ch.challenged_name)}</strong>
                        · ${escHtml(ch.quiz_title)}
                        <span class="time">${statusLabel(ch.status)} · ${new Date(ch.created_at).toLocaleDateString('fr-FR')}</span>
                    </div>
                    ${ch.winner_name ? `<span class="badge badge-admin">🏆 ${escHtml(ch.winner_name)}</span>` : ''}
                </div>
            `).join('');
        }

        // Modal défi
        document.getElementById('btnChallenge')?.addEventListener('click', async () => {
            const modal = document.getElementById('modalChallenge');
            modal?.classList.remove('hidden');

            // Charger les quiz disponibles
            const qSel = document.getElementById('challengeQuiz');
            if (qSel && !qSel.children.length) {
                const r = await fetch(APP_URL + '/api/quiz.php?action=list');
                const d = await r.json();
                qSel.innerHTML = d.data?.map(q => `<option value="${q.id}">${escHtml(q.title)}</option>`).join('') || '';
            }

            // Charger les utilisateurs
            const uSel = document.getElementById('challengeUser');
            if (uSel && !uSel.children.length) {
                const r2 = await fetch(APP_URL + '/api/challenge.php?action=users');
                const d2 = await r2.json();
                uSel.innerHTML = d2.data?.map(u => `<option value="${u.id}">${escHtml(u.display_name)}</option>`).join('') || '';
            }
        });

        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => document.getElementById(btn.dataset.modal)?.classList.add('hidden'));
        });

        document.getElementById('btnSendChallenge')?.addEventListener('click', async () => {
            const quizId      = document.getElementById('challengeQuiz')?.value;
            const challengedId = document.getElementById('challengeUser')?.value;

            const r = await fetch(APP_URL + '/api/challenge.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quiz_id: quizId, challenged_id: challengedId, csrf_token: CSRF_TOKEN }),
            });
            const d = await r.json();
            if (d.success) {
                document.getElementById('modalChallenge')?.classList.add('hidden');
                alert('Défi envoyé ! ⚔️');
            } else {
                alert(d.error || 'Erreur.');
            }
        });

        // Répondre à un défi
        window.respondChallenge = async function (challengeId, accept) {
            const r = await fetch(APP_URL + '/api/challenge.php?action=respond', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ challenge_id: challengeId, accept, csrf_token: CSRF_TOKEN }),
            });
            const d = await r.json();
            if (d.success && accept) {
                window.location.href = APP_URL + '/pages/quizz_play.php?quiz_id=' + d.data.quiz_id + '&challenge_id=' + challengeId;
            } else if (d.success) {
                location.reload();
            } else {
                alert(d.error || 'Erreur.');
            }
        };

        loadQuizzes();
        loadHistory();
        loadChallenges();
    }

    // ============================================================
    // PAGE QUIZZ_PLAY.PHP — session de quiz
    // ============================================================
    if (isPlayPage) {
        let sessionId   = null;
        let questions   = [];
        let currentQ    = 0;
        let answers     = {};
        let timerInterval = null;
        let timeLeft    = 60;

        // Démarrer la session
        async function startQuiz() {
            const r = await fetch(APP_URL + '/api/quiz.php?action=start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ quiz_id: QUIZ_ID, challenge_id: CHALLENGE_ID }),
            });
            const d = await r.json();
            if (!d.success) { document.getElementById('quizBody').innerHTML = '<p style="color:red">Erreur de démarrage.</p>'; return; }
            sessionId = d.data.session_id;
            questions = d.data.questions;
            renderQuestion(0);
        }

        function renderQuestion(index) {
            currentQ = index;
            const q  = questions[index];
            const total = questions.length;

            document.getElementById('questionCounter').textContent = `Question ${index + 1} / ${total}`;
            document.getElementById('progressBar').style.width = `${((index) / total) * 100}%`;

            document.getElementById('quizBody').innerHTML = `
                <div class="quiz-question">${escHtml(q.question_text)}</div>
                <div class="quiz-options">
                    ${q.options.map((opt, i) => `
                        <div class="quiz-option ${answers[q.id] === i ? 'selected' : ''}"
                             data-index="${i}" onclick="selectOption(this, ${q.id}, ${i})">
                            <span style="font-weight:700;color:#e94560;min-width:20px">${String.fromCharCode(65 + i)}.</span>
                            ${escHtml(opt)}
                        </div>
                    `).join('')}
                </div>
            `;

            document.getElementById('btnPrev').disabled = index === 0;
            document.getElementById('btnNext').classList.toggle('hidden', index === total - 1);
            document.getElementById('btnSubmit').classList.toggle('hidden', index !== total - 1);

            // Timer
            clearInterval(timerInterval);
            timeLeft = 60;
            updateTimer();
            timerInterval = setInterval(() => {
                timeLeft--;
                updateTimer();
                if (timeLeft <= 0) { clearInterval(timerInterval); autoNextQuestion(); }
            }, 1000);
        }

        function updateTimer() {
            const el = document.getElementById('timerDisplay');
            if (el) {
                el.textContent = timeLeft + 's';
                el.style.background = timeLeft <= 10 ? '#c73652' : '#e94560';
            }
        }

        function autoNextQuestion() {
            if (currentQ < questions.length - 1) renderQuestion(currentQ + 1);
            else submitQuiz();
        }

        window.selectOption = function (el, questionId, index) {
            answers[questionId] = index;
            document.querySelectorAll('.quiz-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
        };

        document.getElementById('btnNext')?.addEventListener('click', () => {
            if (currentQ < questions.length - 1) renderQuestion(currentQ + 1);
        });

        document.getElementById('btnPrev')?.addEventListener('click', () => {
            if (currentQ > 0) renderQuestion(currentQ - 1);
        });

        document.getElementById('btnSubmit')?.addEventListener('click', submitQuiz);

        async function submitQuiz() {
            clearInterval(timerInterval);
            const r = await fetch(APP_URL + '/api/quiz.php?action=submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: sessionId, answers }),
            });
            const d = await r.json();
            if (!d.success) { alert('Erreur lors de la soumission.'); return; }

            showResults(d.data);
        }

        function showResults(data) {
            document.getElementById('quizResults')?.classList.remove('hidden');
            document.getElementById('scorePercent').textContent = data.score + '%';
            document.getElementById('progressBar').style.width = '100%';

            const circle = document.getElementById('scoreCircle');
            if (data.score >= 80) circle.style.background = 'linear-gradient(135deg, #4ecdc4, #2ebdb4)';
            else if (data.score < 50) circle.style.background = 'linear-gradient(135deg, #e74c3c, #c0392b)';

            document.getElementById('resultsTitle').textContent =
                data.score >= 80 ? '🎉 Excellent !' : data.score >= 60 ? '👍 Bien joué !' : '💪 Continuez à pratiquer !';
            document.getElementById('resultsSubtitle').textContent =
                `${data.correct} bonne${data.correct > 1 ? 's' : ''} réponse${data.correct > 1 ? 's' : ''} sur ${data.total}`;

            // Revue des réponses
            const review = document.getElementById('answersReview');
            if (review && data.answers_detail) {
                review.innerHTML = questions.map((q, i) => {
                    const detail = data.answers_detail[q.id];
                    const icon   = detail?.is_correct ? '✅' : '❌';
                    const chosen = detail?.chosen >= 0 ? q.options[detail.chosen] : 'Non répondu';
                    return `
                        <div style="text-align:left;margin-bottom:12px;padding:12px;background:#f9f9fb;border-radius:8px">
                            <strong>${icon} Q${i + 1}: ${escHtml(q.question_text)}</strong>
                            <div style="margin-top:6px;font-size:.88rem;color:#555">
                                Votre réponse: ${escHtml(chosen)}<br>
                                ${!detail?.is_correct ? `Bonne réponse: ${escHtml(q.options[detail?.correct] || '')}` : ''}
                                ${detail?.explanation ? `<em style="color:#888">${escHtml(detail.explanation)}</em>` : ''}
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

        startQuiz();
    }

    // Utils
    function statusLabel(s) {
        const labels = { pending: '⏳ En attente', accepted: '✅ Accepté', declined: '❌ Refusé', completed: '🏁 Terminé' };
        return labels[s] || s;
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})();
