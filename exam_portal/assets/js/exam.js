(() => {
    const form = document.getElementById('exam-form');
    const timer = document.getElementById('exam-timer');
    const status = document.getElementById('save-status');
    if (!form || !timer || !status) return;

    let remaining = Number(timer.dataset.seconds);
    const pendingSaves = new Set();
    const failedQuestions = new Set();
    let saveQueue = Promise.resolve();
    let finishing = false;
    let timeExpired = false;

    const renderTimer = () => {
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        timer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        timer.classList.toggle('timer-warning', remaining <= 60);
    };

    const saveAnswer = async (input) => {
        const questionId = input.dataset.questionId;
        const request = saveQueue.then(async () => {
            const response = await fetch(form.dataset.saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': form.dataset.csrf,
                },
                body: JSON.stringify({
                    attempt_id: Number(form.elements.attempt_id.value),
                    question_id: Number(input.dataset.questionId),
                    option_id: Number(input.value),
                }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Unable to save your answer.');
            failedQuestions.delete(questionId);
            if (failedQuestions.size === 0) {
                status.textContent = 'All changes saved.';
                status.classList.remove('text-error');
            }
        }).catch((error) => {
            failedQuestions.add(questionId);
            status.textContent = error.message;
            status.classList.add('text-error');
        });
        saveQueue = request;
        pendingSaves.add(request);
        await request;
        pendingSaves.delete(request);
    };

    form.addEventListener('change', (event) => {
        if (event.target.matches('input[type="radio"][data-question-id]')) {
            status.textContent = 'Saving your answer…';
            saveAnswer(event.target);
        }
    });

    form.addEventListener('submit', async (event) => {
        if (finishing) return;
        event.preventDefault();
        finishing = true;
        const button = document.getElementById('submit-exam');
        button.disabled = true;
        form.querySelectorAll('input[type="radio"]').forEach((input) => {
            input.disabled = true;
        });
        status.textContent = 'Saving answers and submitting…';
        await Promise.all([...pendingSaves]);
        if (failedQuestions.size > 0 && !timeExpired) {
            finishing = false;
            button.disabled = false;
            form.querySelectorAll('input[type="radio"]').forEach((input) => {
                input.disabled = false;
            });
            status.textContent += ' Please check your connection, change the affected answer, and submit again.';
            return;
        }
        form.submit();
    });

    renderTimer();
    const countdown = window.setInterval(() => {
        remaining -= 1;
        renderTimer();
        if (remaining <= 0) {
            window.clearInterval(countdown);
            timeExpired = true;
            if (!finishing) form.requestSubmit();
        }
    }, 1000);
})();
