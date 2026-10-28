(() => {
    'use strict';

    const app = document.getElementById('app');
    const navigation = document.getElementById('navigation');
    const appBase = document.baseURI;
    const endpoint = new URL('api/index.php', appBase).toString();
    const pageFiles = {
        '/login': 'login.html',
        '/register': 'register.html',
        '/student/dashboard': 'student/dashboard.html',
        '/student/exam': 'student/exam-instructions.html',
        '/student/attempt': 'student/take-exam.html',
        '/student/results': 'student/results.html',
        '/student/result': 'student/view-result.html',
        '/admin/dashboard': 'admin/dashboard.html',
        '/admin/exams': 'admin/exams.html',
        '/admin/exam-create': 'admin/create-exam.html',
        '/admin/questions': 'admin/questions.html',
        '/admin/question-create': 'admin/add-question.html',
        '/admin/results': 'admin/results.html',
    };
    let csrf = '';
    let currentUser = null;
    let activeTimer = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);

    const formatDate = (value) => {
        if (!value) return 'No limit';
        const date = new Date(`${String(value).replace(' ', 'T')}Z`);
        return Number.isNaN(date.getTime()) ? escapeHtml(value) : escapeHtml(date.toLocaleString());
    };

    const api = async (action, method = 'GET', body = null) => {
        const options = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-Token'] = csrf;
            options.body = JSON.stringify(body);
        }
        const url = new URL(endpoint);
        url.searchParams.set('action', action);
        const response = await fetch(url, options);
        let result;
        try {
            result = await response.json();
        } catch {
            throw new Error('The server returned an invalid response. Check the PHP error log.');
        }
        if (!response.ok) {
            const details = result.errors || (result.error ? [result.error] : ['The request could not be completed.']);
            throw new Error(details.join(' '));
        }
        return result;
    };

    const routeUrl = (route) => {
        const questionIndex = route.indexOf('?');
        const path = questionIndex < 0 ? route : route.slice(0, questionIndex);
        const query = questionIndex < 0 ? '' : route.slice(questionIndex);
        const file = pageFiles[path];
        if (!file) throw new Error(`Unknown page route: ${path}`);
        return new URL(`${file}${query}`, appBase).toString();
    };

    const link = (route, label, className = '') =>
        `<a${className ? ` class="${className}"` : ''} href="${escapeHtml(routeUrl(route))}">${label}</a>`;

    const setNavigation = () => {
        if (!currentUser) {
            navigation.innerHTML = `${link('/login', 'Sign in')}${link('/register', 'Register', 'button button-small')}`;
            return;
        }
        const homeRoute = currentUser.role === 'admin' ? '/admin/dashboard' : '/student/dashboard';
        navigation.innerHTML = [
            link(homeRoute, 'Dashboard'),
            currentUser.role === 'admin' ? link('/admin/exams', 'Exams') : link('/student/results', 'Results'),
            `<span class="nav-user">${escapeHtml(currentUser.name)}</span>`,
            '<button class="link-button" type="button" data-action="logout">Sign out</button>',
        ].join('');
    };

    const show = (html) => {
        app.innerHTML = html;
        app.scrollIntoView({ block: 'start' });
    };

    const pageHeading = (eyebrow, title, description = '', action = '') =>
        `<div class="page-heading"><div><p class="eyebrow">${eyebrow}</p><h1>${title}</h1>${description ? `<p class="muted">${description}</p>` : ''}</div>${action}</div>`;

    const table = (headings, rows) => rows.length
        ? `<div class="table-wrap"><table><thead><tr>${headings.map((item) => `<th>${item}</th>`).join('')}</tr></thead><tbody>${rows.join('')}</tbody></table></div>`
        : '<div class="empty-state">There is nothing to show yet.</div>';

    const renderLogin = () => show(`
        <section class="auth-card">
            <h1>Welcome back</h1>
            <p class="muted">Sign in to continue to your ExamPortal account.</p>
            <div data-error></div>
            <form data-form="login">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required maxlength="190">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="button" type="submit">Sign in</button>
            </form>
            <p class="auth-switch">New to ExamPortal? ${link('/register', 'Create an account')}</p>
        </section>`);

    const renderRegister = () => show(`
        <section class="auth-card">
            <h1>Create a student account</h1>
            <p class="muted">Register to access and take available examinations.</p>
            <div data-error></div>
            <form data-form="register">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" autocomplete="name" required maxlength="120">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" autocomplete="email" required maxlength="190">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                <button class="button" type="submit">Create account</button>
            </form>
            <p class="auth-switch">Already registered? ${link('/login', 'Sign in')}</p>
        </section>`);

    const renderStudentDashboard = async () => {
        const data = await api('student/dashboard');
        const exams = data.available.map((exam) => `<article class="card">
            <h3>${escapeHtml(exam.title)}</h3>
            <p class="muted">${escapeHtml(exam.description || 'No additional instructions provided.')}</p>
            <p class="meta">${Number(exam.question_count)} questions · ${Number(exam.duration_minutes)} minutes</p>
            ${link(`/student/exam?id=${Number(exam.id)}`, 'View exam', 'button')}
        </article>`).join('');
        const attempts = data.attempts.map((attempt) => `<tr>
            <td>${escapeHtml(attempt.title)}</td>
            <td><span class="status status-${escapeHtml(attempt.status)}">${escapeHtml(attempt.status.replace('_', ' '))}</span></td>
            <td>${formatDate(attempt.started_at)}</td>
            <td>${attempt.status === 'submitted' ? Number(attempt.score) : '—'}</td>
            <td>${attempt.status === 'submitted'
                ? link(`/student/result?id=${Number(attempt.id)}`, 'View result')
                : link(`/student/attempt?id=${Number(attempt.id)}`, 'Continue exam')}</td>
        </tr>`).join('');
        show(`${pageHeading('STUDENT PORTAL', 'Your dashboard', 'Choose an available exam or continue an attempt in progress.', link('/student/results', 'View results', 'button button-outline'))}
            <section class="section-block"><h2>Available exams</h2>${exams ? `<div class="card-grid">${exams}</div>` : '<div class="empty-state">There are no exams open right now. Check back later.</div>'}</section>
            <section class="section-block"><h2>Your exam activity</h2>${table(['Exam', 'Status', 'Started', 'Score', ''], attempts)}</section>`);
    };

    const renderExam = async (examId) => {
        const data = await api(`student/exam&id=${encodeURIComponent(examId)}`);
        const exam = data.exam;
        const action = data.attempt
            ? data.attempt.status === 'submitted'
                ? link(`/student/result?id=${Number(data.attempt.id)}`, 'View submitted result', 'button')
                : `<form data-form="start-exam"><input type="hidden" name="exam_id" value="${Number(exam.id)}"><button class="button" type="submit">Continue exam</button></form>`
            : data.open
                ? `<form data-form="start-exam"><input type="hidden" name="exam_id" value="${Number(exam.id)}"><button class="button" type="submit">Start exam</button></form>`
                : '<div class="notice notice-error">This exam is not currently open.</div>';
        show(`<section class="content-card narrow">
            <p class="eyebrow">EXAM INSTRUCTIONS</p><h1>${escapeHtml(exam.title)}</h1>
            <p>${escapeHtml(exam.description || 'Read each question carefully and submit your answers before the timer expires.').replace(/\n/g, '<br>')}</p>
            <p class="muted">Availability dates control when an exam can be started. Once started, you have the full time limit shown below.</p>
            <div class="info-grid">
                <div><span>Questions</span><strong>${Number(exam.question_count)}</strong></div>
                <div><span>Time allowed</span><strong>${Number(exam.duration_minutes)} minutes</strong></div>
                <div><span>Available until</span><strong>${formatDate(exam.ends_at)}</strong></div>
            </div>
            <div data-error></div>${action}
        </section>`);
    };

    const renderAttempt = async (attemptId) => {
        const data = await api(`student/attempt&id=${encodeURIComponent(attemptId)}`);
        if (data.submitted) {
            location.assign(routeUrl(`/student/result?id=${Number(data.attempt_id)}`));
            return;
        }
        const questions = data.questions.map((question, index) => `<fieldset class="question-card">
            <legend><span class="question-number">Question ${index + 1}</span>${escapeHtml(question.text).replace(/\n/g, '<br>')}</legend>
            <p class="muted">${Number(question.marks)} ${Number(question.marks) === 1 ? 'mark' : 'marks'}</p>
            ${question.options.map((option) => `<label class="option">
                <input type="radio" name="question_${Number(question.id)}" value="${Number(option.id)}"
                    data-question-id="${Number(question.id)}" ${Number(question.selected_option_id) === Number(option.id) ? 'checked' : ''}>
                <span>${escapeHtml(option.text)}</span>
            </label>`).join('')}
        </fieldset>`).join('');
        if (!questions) {
            throw new Error('This exam currently has no questions. Contact the exam administrator.');
        }
        show(`<div class="exam-topbar"><div><p class="eyebrow">IN PROGRESS</p><h1>${escapeHtml(data.attempt.title)}</h1></div>
            <div class="timer" id="exam-timer" data-seconds="${Number(data.seconds_left)}" aria-live="polite">--:--</div></div>
            <p class="muted">Your answers save automatically when you select an option. Submit when you are finished.</p>
            <div id="save-status" class="save-status" role="status" aria-live="polite"></div>
            <div data-error></div><form data-form="submit-exam" data-attempt-id="${Number(data.attempt.id)}">
                ${questions}<div class="submit-row"><span class="muted">${data.questions.length} questions</span>
                <button class="button" id="submit-exam" type="submit">Submit exam</button></div>
            </form>`);
        startTimer(Number(data.seconds_left), Number(data.attempt.id));
    };

    const renderStudentResults = async () => {
        const data = await api('student/results');
        const rows = data.results.map((result) => `<tr>
            <td>${escapeHtml(result.title)}</td>
            <td>${Number(result.score)} / ${Number(result.total_marks)}</td>
            <td>${formatDate(result.submitted_at)}</td>
            <td>${link(`/student/result?id=${Number(result.id)}`, 'Review answers')}</td>
        </tr>`).join('');
        show(`${pageHeading('STUDENT PORTAL', 'My results', 'Your completed exam attempts and scores.')}
            ${rows ? table(['Exam', 'Score', 'Submitted', ''], rows) : '<div class="empty-state">No submitted exam results yet.</div>'}`);
    };

    const renderResult = async (attemptId) => {
        const data = await api(`student/result&id=${encodeURIComponent(attemptId)}`);
        const attempt = data.attempt;
        const answers = data.answers.map((answer, index) => `<article class="review-card">
            <div class="review-heading"><h3>Question ${index + 1}</h3>
            <span class="${Number(answer.is_correct) ? 'text-success' : 'text-error'}">${Number(answer.is_correct) ? 'Correct' : 'Incorrect'}</span></div>
            <p>${escapeHtml(answer.question_text).replace(/\n/g, '<br>')}</p>
            <p class="muted">Your answer: <strong>${escapeHtml(answer.selected_text || 'Not answered')}</strong></p>
            ${Number(answer.is_correct) ? '' : `<p class="muted">Correct answer: <strong>${escapeHtml(answer.correct_text || 'Not available')}</strong></p>`}
            <p class="meta">${Number(answer.marks)} ${Number(answer.marks) === 1 ? 'mark' : 'marks'}</p>
        </article>`).join('');
        const back = currentUser.role === 'admin' ? '/admin/results' : '/student/results';
        show(`<div class="page-heading"><div><p class="eyebrow">RESULT</p><h1>${escapeHtml(attempt.title)}</h1>
            <p class="muted">Submitted ${formatDate(attempt.submitted_at)}</p></div>
            <div class="score-card"><strong>${Number(attempt.score)} / ${Number(attempt.total_marks)}</strong><span>points</span></div></div>
            <section class="section-block"><h2>Answer review</h2>${answers}</section>
            ${link(back, 'Back to results', 'button button-outline')}`);
    };

    const renderAdminDashboard = async () => {
        const { stats } = await api('admin/dashboard');
        show(`${pageHeading('ADMINISTRATION', 'Dashboard', 'Create examinations, manage questions, and review submissions.', link('/admin/exam-create', 'Create exam', 'button'))}
            <div class="stat-grid">
                <div class="stat-card"><span>All exams</span><strong>${Number(stats.exams)}</strong></div>
                <div class="stat-card"><span>Published exams</span><strong>${Number(stats.published)}</strong></div>
                <div class="stat-card"><span>Students</span><strong>${Number(stats.students)}</strong></div>
                <div class="stat-card"><span>Submissions</span><strong>${Number(stats.submissions)}</strong></div>
            </div>
            <div class="quick-links">
                ${link('/admin/exams', '<h2>Manage exams</h2><p class="muted">Publish exams and add questions.</p>', 'card')}
                ${link('/admin/results', '<h2>Review results</h2><p class="muted">See submitted scores and student attempts.</p>', 'card')}
            </div>`);
    };

    const renderAdminExams = async () => {
        const { exams } = await api('admin/exams');
        const rows = exams.map((exam) => `<tr>
            <td><strong>${escapeHtml(exam.title)}</strong><br><span class="muted">${Number(exam.duration_minutes)} min</span></td>
            <td><span class="status status-${escapeHtml(exam.status)}">${escapeHtml(exam.status)}</span></td>
            <td>${Number(exam.question_count)}</td><td>${Number(exam.attempt_count)}</td>
            <td>${formatDate(exam.starts_at)}<br>to ${formatDate(exam.ends_at)}</td>
            <td class="actions">${link(`/admin/questions?id=${Number(exam.id)}`, 'Questions')}
                <button class="link-button" type="button" data-action="publish" data-exam-id="${Number(exam.id)}">${exam.status === 'draft' ? 'Publish' : 'Unpublish'}</button></td>
        </tr>`).join('');
        show(`${pageHeading('ADMINISTRATION', 'Exams', 'Draft exams are private. Publish an exam to make it available to students.', link('/admin/exam-create', 'Create exam', 'button'))}
            ${rows ? table(['Exam', 'Status', 'Questions', 'Attempts', 'Availability', 'Actions'], rows) : '<div class="empty-state">No exams yet. Create your first exam to get started.</div>'}
            <div data-error></div>`);
    };

    const renderExamCreate = () => show(`<section class="content-card narrow">
        <p class="eyebrow">EXAM SETUP</p><h1>Create an exam</h1><div data-error></div>
        <form data-form="create-exam">
            <label for="title">Exam title</label><input id="title" name="title" maxlength="180" required>
            <label for="description">Instructions / description</label><textarea id="description" name="description" rows="5"></textarea>
            <label for="duration_minutes">Time limit (minutes)</label><input id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" value="30" required>
            <label for="starts_at">Available from, UTC (optional)</label><input id="starts_at" name="starts_at" type="datetime-local">
            <label for="ends_at">Available until, UTC (optional)</label><input id="ends_at" name="ends_at" type="datetime-local">
            <button class="button" type="submit">Create draft exam</button>
        </form></section>`);

    const renderAdminQuestions = async (examId) => {
        const data = await api(`admin/questions&id=${encodeURIComponent(examId)}`);
        const content = data.questions.map((question, index) => `<article class="review-card">
            <h2>Question ${index + 1} <span class="muted">· ${Number(question.marks)} ${Number(question.marks) === 1 ? 'mark' : 'marks'}</span></h2>
            <p>${escapeHtml(question.text).replace(/\n/g, '<br>')}</p>
            <ul class="option-list">${question.options.map((option) => `<li class="${option.correct ? 'text-success' : ''}">${escapeHtml(option.text)}${option.correct ? ' (correct)' : ''}</li>`).join('')}</ul>
        </article>`).join('');
        const addButton = data.editable ? `<p>${link(`/admin/question-create?id=${Number(data.exam.id)}`, 'Add a question', 'button')}</p>`
            : '<div class="notice notice-info">Questions are read-only after publishing or once an exam has attempts.</div>';
        show(`${pageHeading('EXAM QUESTIONS', escapeHtml(data.exam.title), `${data.questions.length} questions · ${escapeHtml(data.exam.status)}`, link('/admin/exams', 'Back to exams', 'button button-outline'))}
            ${addButton}${content || '<div class="empty-state">No questions have been added to this exam.</div>'}`);
    };

    const renderQuestionCreate = async (examId) => {
        const data = await api(`admin/questions&id=${encodeURIComponent(examId)}`);
        if (!data.editable) {
            throw new Error('Questions cannot be changed after this exam is published or has attempts.');
        }
        show(`<section class="content-card narrow"><p class="eyebrow">${escapeHtml(data.exam.title)}</p>
            <h1>Add a multiple-choice question</h1><div data-error></div>
            <form data-form="create-question" data-exam-id="${Number(data.exam.id)}">
                <label for="question_text">Question</label><textarea id="question_text" name="question_text" rows="4" maxlength="10000" required></textarea>
                <label for="marks">Marks</label><input id="marks" name="marks" type="number" min="1" max="1000" value="1" required>
                <fieldset class="choice-fields"><legend>Answer choices (select the correct one)</legend>
                    ${Array.from({ length: 6 }, (_, index) => `<div class="choice-row">
                        <input type="radio" name="correct_option" value="${index}" aria-label="Mark choice ${index + 1} as correct" ${index === 0 ? 'checked' : ''}>
                        <input name="option_${index}" maxlength="500" placeholder="Choice ${index + 1}" ${index < 2 ? 'required' : ''}>
                    </div>`).join('')}
                </fieldset><button class="button" type="submit">Save question</button>
            </form></section>`);
    };

    const renderAdminResults = async () => {
        const { results } = await api('admin/results');
        const rows = results.map((result) => `<tr>
            <td><strong>${escapeHtml(result.student_name)}</strong><br><span class="muted">${escapeHtml(result.email)}</span></td>
            <td>${escapeHtml(result.title)}</td><td>${Number(result.score)} / ${Number(result.total_marks)}</td>
            <td>${formatDate(result.submitted_at)}</td><td>${link(`/student/result?id=${Number(result.id)}`, 'Review')}</td>
        </tr>`).join('');
        show(`${pageHeading('ADMINISTRATION', 'Exam results', 'Scores for all submitted examination attempts.')}
            ${rows ? table(['Student', 'Exam', 'Score', 'Submitted', ''], rows) : '<div class="empty-state">There are no submitted exams yet.</div>'}`);
    };

    const render = async () => {
        if (activeTimer) {
            clearInterval(activeTimer);
            activeTimer = null;
        }
        const [session] = await Promise.all([api('session')]);
        currentUser = session.user;
        csrf = session.csrf;
        setNavigation();
        const route = document.body.dataset.route || (location.hash ? location.hash.slice(1).split('?')[0] : '')
            || (currentUser ? `/${currentUser.role}/dashboard` : '/login');
        const params = new URLSearchParams(location.search);
        if (!params.has('id') && location.hash.includes('?')) {
            const hashParams = new URLSearchParams(location.hash.slice(location.hash.indexOf('?') + 1));
            if (hashParams.has('id')) params.set('id', hashParams.get('id'));
        }
        const id = params.get('id');
        if (!currentUser && !['/login', '/register'].includes(route)) {
            location.assign(routeUrl('/login'));
            return;
        }
        if (currentUser && (route === '/login' || route === '/register')) {
            location.assign(routeUrl(`/${currentUser.role}/dashboard`));
            return;
        }
        try {
            switch (route) {
                case '/login': renderLogin(); break;
                case '/register': renderRegister(); break;
                case '/student/dashboard': await renderStudentDashboard(); break;
                case '/student/exam': await renderExam(id); break;
                case '/student/attempt': await renderAttempt(id); break;
                case '/student/results': await renderStudentResults(); break;
                case '/student/result': await renderResult(id); break;
                case '/admin/dashboard': await renderAdminDashboard(); break;
                case '/admin/exams': await renderAdminExams(); break;
                case '/admin/exam-create': renderExamCreate(); break;
                case '/admin/questions': await renderAdminQuestions(id); break;
                case '/admin/question-create': await renderQuestionCreate(id); break;
                case '/admin/results': await renderAdminResults(); break;
                default: show(`<div class="empty-state">Page not found. ${link(currentUser ? `/${currentUser.role}/dashboard` : '/login', 'Return to a valid page')}</div>`);
            }
        } catch (error) {
            show(`<div class="notice notice-error" role="alert">${escapeHtml(error.message)}<p>${link(currentUser ? `/${currentUser.role}/dashboard` : '/login', 'Return to a valid page')}</p></div>`);
        }
    };

    const displayError = (error) => {
        const target = app.querySelector('[data-error]');
        if (target) target.innerHTML = `<div class="notice notice-error" role="alert">${escapeHtml(error.message)}</div>`;
        else show(`<div class="notice notice-error" role="alert">${escapeHtml(error.message)}</div>`);
    };

    const navigateToRoleDashboard = () => {
        location.assign(routeUrl(currentUser.role === 'admin' ? '/admin/dashboard' : '/student/dashboard'));
    };

    const startTimer = (seconds, attemptId) => {
        const timer = document.getElementById('exam-timer');
        const status = document.getElementById('save-status');
        const form = app.querySelector('[data-form="submit-exam"]');
        const button = document.getElementById('submit-exam');
        let remaining = seconds;
        let finishing = false;
        let expired = false;
        let saveQueue = Promise.resolve();
        const pending = new Set();
        const failed = new Set();
        const updateTimer = () => {
            const minutes = Math.floor(remaining / 60);
            const secondsLeft = remaining % 60;
            timer.textContent = `${String(minutes).padStart(2, '0')}:${String(secondsLeft).padStart(2, '0')}`;
            timer.classList.toggle('timer-warning', remaining <= 60);
        };
        const save = (input) => {
            const questionId = input.dataset.questionId;
            const task = saveQueue.then(async () => {
                await api('student/answer', 'POST', {
                    attempt_id: attemptId, question_id: Number(questionId), option_id: Number(input.value),
                });
                failed.delete(questionId);
                if (!failed.size) {
                    status.textContent = 'All changes saved.';
                    status.classList.remove('text-error');
                }
            }).catch((error) => {
                failed.add(questionId);
                status.textContent = error.message;
                status.classList.add('text-error');
            });
            saveQueue = task;
            pending.add(task);
            task.finally(() => pending.delete(task));
        };
        form.addEventListener('change', (event) => {
            if (event.target.matches('input[type="radio"][data-question-id]')) {
                status.textContent = 'Saving your answer…';
                save(event.target);
            }
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (finishing) return;
            finishing = true;
            button.disabled = true;
            form.querySelectorAll('input[type="radio"]').forEach((input) => { input.disabled = true; });
            status.textContent = 'Saving answers and submitting…';
            await Promise.all([...pending]);
            if (failed.size && !expired) {
                finishing = false;
                button.disabled = false;
                form.querySelectorAll('input[type="radio"]').forEach((input) => { input.disabled = false; });
                status.textContent = 'Some answers could not be saved. Check your connection, change an affected answer, and submit again.';
                return;
            }
            try {
                if (expired && failed.size) {
                    window.alert('Time expired. One or more answers could not be saved, so only answers already saved by the server will be scored.');
                }
                await api('student/submit', 'POST', { attempt_id: attemptId });
                location.assign(routeUrl(`/student/result?id=${attemptId}`));
            } catch (error) {
                finishing = false;
                button.disabled = false;
                displayError(error);
            }
        });
        updateTimer();
        activeTimer = window.setInterval(() => {
            remaining -= 1;
            updateTimer();
            if (remaining <= 0) {
                clearInterval(activeTimer);
                expired = true;
                if (!finishing) form.requestSubmit();
            }
        }, 1000);
    };

    app.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-form]');
        if (!form) return;
        if (form.dataset.form === 'submit-exam') return;
        event.preventDefault();
        const formData = new FormData(form);
        const values = Object.fromEntries(formData.entries());
        try {
            switch (form.dataset.form) {
                case 'login': {
                    const data = await api('login', 'POST', values);
                    currentUser = data.user;
                    csrf = data.csrf;
                    setNavigation();
                    navigateToRoleDashboard();
                    break;
                }
                case 'register': {
                    const data = await api('register', 'POST', values);
                    currentUser = data.user;
                    csrf = data.csrf;
                    setNavigation();
                    navigateToRoleDashboard();
                    break;
                }
                case 'start-exam': {
                    const data = await api('student/start', 'POST', { exam_id: Number(values.exam_id) });
                    location.assign(routeUrl(data.status === 'submitted'
                        ? `/student/result?id=${data.attempt_id}`
                        : `/student/attempt?id=${data.attempt_id}`));
                    break;
                }
                case 'create-exam': {
                    const data = await api('admin/exam/create', 'POST', values);
                    location.assign(routeUrl(`/admin/questions?id=${data.exam_id}`));
                    break;
                }
                case 'create-question': {
                    const optionRows = Array.from({ length: 6 }, (_, index) => ({
                        originalIndex: index,
                        text: (values[`option_${index}`] || '').trim(),
                    })).filter((option) => option.text !== '');
                    const correctOriginal = Number(values.correct_option);
                    const correctIndex = optionRows.findIndex((option) => option.originalIndex === correctOriginal);
                    await api('admin/question/create', 'POST', {
                        exam_id: Number(form.dataset.examId),
                        question_text: values.question_text,
                        marks: Number(values.marks),
                        options: optionRows.map((option) => option.text),
                        correct_option: correctIndex < 0 ? 999 : correctIndex,
                    });
                    location.assign(routeUrl(`/admin/questions?id=${form.dataset.examId}`));
                    break;
                }
                case 'submit-exam':
                    await api('student/submit', 'POST', { attempt_id: Number(form.dataset.attemptId) });
                    location.assign(routeUrl(`/student/result?id=${form.dataset.attemptId}`));
                    break;
                default:
                    throw new Error('This form is not supported.');
            }
        } catch (error) {
            displayError(error);
        }
    });

    app.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        try {
            if (button.dataset.action === 'logout') {
                await api('logout', 'POST', {});
                currentUser = null;
                setNavigation();
                location.assign(routeUrl('/login'));
            } else if (button.dataset.action === 'publish') {
                await api('admin/exam/publish', 'POST', { exam_id: Number(button.dataset.examId) });
                await renderAdminExams();
            }
        } catch (error) {
            displayError(error);
        }
    });

    render();
})();
