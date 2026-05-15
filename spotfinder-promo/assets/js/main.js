(function () {
    const nav = document.querySelector('.site-nav');
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelectorAll('.nav-links a');
    const revealItems = document.querySelectorAll('.reveal');
    const statsSection = document.getElementById('stats');
    const counters = document.querySelectorAll('.counter');
    const video = document.getElementById('promoVideo');
    const skipButtons = document.querySelectorAll('.video-skip');

    if (nav && navToggle) {
        navToggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('nav-open');
            navToggle.setAttribute('aria-expanded', String(isOpen));
            navToggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
        });

        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('nav-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', 'Buka menu');
            });
        });
    }

    function revealVisibleItems() {
        const windowHeight = window.innerHeight;

        revealItems.forEach(function (item) {
            const elementTop = item.getBoundingClientRect().top;
            const elementVisible = 150;

            if (elementTop < windowHeight - elementVisible) {
                item.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', revealVisibleItems, { passive: true });
    revealVisibleItems();

    skipButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (!video) {
                return;
            }

            const skipBy = Number(button.dataset.skip || 0);
            video.currentTime = Math.max(0, video.currentTime + skipBy);
        });
    });

    function animateCounters() {
        counters.forEach(function (counter) {
            const target = Number(counter.dataset.target || 0);
            const duration = 1200;
            const start = performance.now();

            function update(now) {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                counter.textContent = String(Math.round(target * eased));

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    counter.textContent = String(target);
                }
            }

            requestAnimationFrame(update);
        });
    }

    if (statsSection && counters.length > 0) {
        const statsObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        statsObserver.observe(statsSection);
    }
})();
