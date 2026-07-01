(function () {
    'use strict';

    var pinWrap = document.getElementById('pjPinWrap');
    var track = document.getElementById('pjTrack');
    var fill = document.getElementById('pjProgFill');
    var counter = document.getElementById('pjProgNum');

    if (!pinWrap || !track) return;

    var inner = pinWrap.querySelector('.pj-pin-inner');
    var maxX = 0, target = 0, current = 0;
    var speed = 1.35;

    // Featured card entrance animation
    var featured = document.getElementById('pjFeatured');
    if (featured) {
        requestAnimationFrame(function () {
            featured.classList.add('in');
        });
    }

    // Regular cards: staggered reveal
    var projCards = Array.prototype.slice.call(document.querySelectorAll('.proj-card'));
    projCards.forEach(function (c, i) {
        c.style.transitionDelay = (i % 3 * 0.05) + 's';
    });

    function revealVisibleCards() {
        var vw = window.innerWidth;
        projCards.forEach(function (c) {
            if (c.classList.contains('inview')) return;
            var r = c.getBoundingClientRect();
            if (r.left < vw * 0.92 && r.right > 0) c.classList.add('inview');
        });
    }

    // Count total items (featured + connector counts as 0, regular cards)
    var totalItems = projCards.length + (featured ? 1 : 0);
    var totEl = document.getElementById('pjProgTotal');
    if (totEl) totEl.textContent = String(totalItems).padStart(2, '0');

    function layout() {
        track.style.transition = 'none';
        var prev = track.style.transform;
        track.style.transform = 'none';
        maxX = Math.max(0, track.scrollWidth - inner.clientWidth);
        pinWrap.style.height = maxX > 0 ? (window.innerHeight + maxX * speed) + 'px' : 'auto';
        track.style.transform = prev || 'none';
        onScroll();
    }

    function onScroll() {
        if (maxX <= 0) {
            target = 0;
            if (fill) fill.style.width = '0%';
            return;
        }
        var top = pinWrap.getBoundingClientRect().top + window.scrollY;
        var dist = pinWrap.offsetHeight - window.innerHeight;
        var p = (window.scrollY - top) / dist;
        p = Math.min(1, Math.max(0, p));
        target = p * maxX;
        if (fill) fill.style.width = (p * 100) + '%';
        var idx = Math.min(totalItems, Math.max(1, Math.round(p * (totalItems - 1)) + 1));
        if (counter) counter.textContent = String(idx).padStart(2, '0');
    }

    function raf() {
        current += (target - current) * 0.09;
        if (Math.abs(target - current) < 0.08) current = target;
        track.style.transform = 'translate3d(' + (-current).toFixed(2) + 'px,0,0)';
        revealVisibleCards();
        requestAnimationFrame(raf);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', layout);
    window.addEventListener('load', layout);
    layout();
    revealVisibleCards();
    requestAnimationFrame(raf);
})();
