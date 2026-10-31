document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('exam-form');
    const timer = document.getElementById('exam-timer');
    if (!form || !timer) return;

    const cards = [...form.querySelectorAll('.question-card')];
    const jumps = [...form.querySelectorAll('.question-jump')];
    const status = document.getElementById('save-status');
    const saves = new Map();
    const queues = new Map();
    let activeIndex = 0;
    let submitting = false;

    const showQuestion = (index) => {
        activeIndex = Math.max(0, Math.min(index, cards.length - 1));
        cards.forEach((card, cardIndex) => {
            card.hidden = cardIndex !== activeIndex;
        });
        jumps.forEach((button, buttonIndex) => {
            button.classList.toggle('active', buttonIndex === activeIndex);
            button.setAttribute('aria-current', buttonIndex === activeIndex ? 'step' : 'false');
        });
    };

    const saveAnswer = (input) => {
        const questionId = input.dataset.questionId;
        const selectedOption = input.value;
        const previous = queues.get(questionId) || Promise.resolve();
        const request = previous.catch(() => {}).then(async () => {
            const response = await fetch(form.dataset.saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': form.dataset.csrf,
                },
                body: JSON.stringify({
                    attempt_id: Number(form.dataset.attemptId),
                    question_id: Number(questionId),
                    selected_option: selectedOption,
                }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Unable to save this answer.');
        });
        queues.set(questionId, request);
        saves.set(questionId, request);
        status.textContent = 'Saving your answer…';
        request.then(() => {
            if (saves.get(questionId) === request) {
                saves.delete(questionId);
                status.textContent = saves.size ? 'Saving your answers…' : 'All answers saved.';
            }
        }).catch((error) => {
            if (saves.get(questionId) === request) {
                saves.delete(questionId);
                status.textContent = `${error.message} Your answer will also be submitted with the form.`;
                status.classList.add('text-error');
            }
        });
    };

    form.addEventListener('change', (event) => {
        if (event.target.matches('input[type="radio"][data-question-id]')) {
            jumps[activeIndex]?.classList.add('answered');
            saveAnswer(event.target);
        }
    });

    form.querySelectorAll('.next-question').forEach((button) => {
        button.addEventListener('click', () => showQuestion(activeIndex + 1));
    });
    form.querySelectorAll('.prev-question').forEach((button) => {
        button.addEventListener('click', () => showQuestion(activeIndex - 1));
    });
    jumps.forEach((button) => {
        button.addEventListener('click', () => showQuestion(Number(button.dataset.jumpIndex)));
    });

    const submit = async () => {
        if (submitting) return;
        submitting = true;
        const submitButton = document.getElementById('submit-exam');
        if (submitButton) submitButton.disabled = true;
        status.textContent = 'Submitting your exam…';
        await Promise.all([...saves.values()].map((promise) => promise.catch(() => {})));
        form.submit();
    };

    form.addEventListener('submit', (event) => {
        if (submitting) return;
        event.preventDefault();
        submit();
    });

    const updateTimer = () => {
        const remaining = Math.max(0, Number(timer.dataset.deadline) - Math.floor(Date.now() / 1000));
        const hours = Math.floor(remaining / 3600);
        const minutes = Math.floor((remaining % 3600) / 60);
        const seconds = remaining % 60;
        timer.textContent = hours > 0
            ? [hours, minutes, seconds].map((value) => String(value).padStart(2, '0')).join(':')
            : `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        timer.classList.toggle('timer-warning', remaining <= 60);
        if (remaining === 0) submit();
    };

    showQuestion(0);
    updateTimer();
    window.setInterval(updateTimer, 1000);
});
