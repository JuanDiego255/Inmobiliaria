(function () {
    'use strict';

    var pinWrap = document.getElementById('pcPinWrap');
    var track = document.getElementById('pcTrack');
    var fill = document.getElementById('pcProgFill');
    var counter = document.getElementById('pcProgNum');

    if (!pinWrap || !track) return;

    var inner = pinWrap.querySelector('.pc-pin-inner');
    var cards = track.querySelectorAll('.pc-card');
    var totalCards = cards.length;
    var maxX = 0, target = 0, current = 0;
    var speed = 1.35;

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
        var idx = Math.min(totalCards, Math.max(1, Math.round(p * (totalCards - 1)) + 1));
        if (counter) counter.textContent = String(idx).padStart(2, '0');
    }

    function raf() {
        current += (target - current) * 0.09;
        if (Math.abs(target - current) < 0.08) current = target;
        track.style.transform = 'translate3d(' + (-current).toFixed(2) + 'px,0,0)';
        requestAnimationFrame(raf);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', layout);
    window.addEventListener('load', layout);
    layout();
    requestAnimationFrame(raf);

    // Favorites toggle
    track.addEventListener('click', function (e) {
        var fav = e.target.closest('.pc-fav');
        if (fav) {
            e.preventDefault();
            e.stopPropagation();
            fav.classList.toggle('on');
            var icon = fav.querySelector('.material-icons');
            icon.textContent = fav.classList.contains('on') ? 'favorite' : 'favorite_border';
        }
    });
})();
