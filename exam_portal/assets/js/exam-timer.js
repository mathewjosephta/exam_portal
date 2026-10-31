document.addEventListener('DOMContentLoaded', () => {
    const timerEl = document.getElementById('countdown');
    if (!timerEl) return;
    let remaining = Number(timerEl.dataset.deadline || 0);
    const tick = () => {
        if (remaining <= 0) {
            timerEl.textContent = '00:00';
            const form = document.getElementById('exam-form');
            if (form) form.submit();
            return;
        }
        const hours = Math.floor(remaining / 3600);
        const minutes = Math.floor((remaining % 3600) / 60);
        const seconds = remaining % 60;
        timerEl.textContent = [hours, minutes, seconds].map((v) => String(v).padStart(2, '0')).join(':');
        remaining -= 1;
    };
    tick();
    setInterval(tick, 1000);
});
