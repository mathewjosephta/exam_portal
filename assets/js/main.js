document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menuToggle');
    const navigation = document.getElementById('mainNav');

    if (toggle && navigation) {
        toggle.addEventListener('click', () => {
            const isOpen = navigation.classList.toggle('open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        navigation.addEventListener('click', (event) => {
            if (event.target.closest('a')) {
                navigation.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const revealElements = document.querySelectorAll(
        '.reveal-on-scroll, .feature-card, .exam-card, .stat-card'
    );
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (revealElements.length && !reduceMotion && 'IntersectionObserver' in window) {
        document.documentElement.classList.add('motion-ready');

        revealElements.forEach((element, index) => {
            element.classList.add('reveal-on-scroll');
            element.style.setProperty('--reveal-delay', `${(index % 4) * 70}ms`);
        });

        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -24px 0px' });

        revealElements.forEach((element) => observer.observe(element));
    }
});
