(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduce && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('inview');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
        document.querySelectorAll('.cb-reveal-up').forEach(function (el) {
            io.observe(el);
        });
    } else {
        document.querySelectorAll('.cb-reveal-up').forEach(function (el) {
            el.classList.add('inview');
        });
    }
})();
