/* ============================================================
   TCAM — Shared premium motion
   Scroll-reveal for .reveal elements + header elevation on scroll.
   Safe to include on any page; no-ops if nothing matches.
   ============================================================ */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var revealEls = document.querySelectorAll('.reveal');
        if (revealEls.length && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            revealEls.forEach(function (el, i) {
                el.style.transitionDelay = (i % 6) * 70 + 'ms';
                observer.observe(el);
            });
        } else {
            revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        }

        var header = document.querySelector('.site-header');
        if (header) {
            var onScroll = function () {
                header.classList.toggle('is-scrolled', window.scrollY > 10);
            };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        }

        // Stat counters: <span class="tcam-counter" data-count-to="500">0</span>
        var counters = document.querySelectorAll('.tcam-counter[data-count-to]');
        if (counters.length && 'IntersectionObserver' in window) {
            var runCounter = function (el) {
                var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;
                var suffix = el.getAttribute('data-suffix') || '';
                var duration = 1400;
                var start = null;
                function step(ts) {
                    if (!start) start = ts;
                    var progress = Math.min((ts - start) / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(eased * target) + suffix;
                    if (progress < 1) requestAnimationFrame(step);
                    else el.textContent = target + suffix;
                }
                requestAnimationFrame(step);
            };
            var counterObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        runCounter(entry.target);
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.4 });
            counters.forEach(function (el) { counterObserver.observe(el); });
        }
    });
})();
