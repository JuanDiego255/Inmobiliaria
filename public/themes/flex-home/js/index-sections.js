/* ======================================================
   Index Sections — Animations, parallax, rail interactions
   ====================================================== */
(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── IntersectionObserver reveal ──────────────────
    if (!reduce && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    en.target.classList.add('inview');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });

        document.querySelectorAll(
            '.ix-reveal-up, .ix-feat-card, .ix-forsale-grid .ix-prop-card, .ix-recent-rail .ix-prop-card'
        ).forEach(function (el) {
            // stagger grid cards
            if (el.parentElement && el.parentElement.classList.contains('ix-forsale-grid')) {
                var idx = Array.prototype.indexOf.call(el.parentElement.children, el);
                el.style.transitionDelay = (idx % 3) * 0.09 + 0.04 + 's';
            }
            // stagger rail cards
            if (el.parentElement && el.parentElement.classList.contains('ix-recent-rail')) {
                var idx2 = Array.prototype.indexOf.call(el.parentElement.children, el);
                el.style.transitionDelay = Math.min(idx2, 6) * 0.08 + 's';
            }
            io.observe(el);
        });
    } else {
        document.querySelectorAll('.ix-reveal-up, .ix-feat-card, .ix-prop-card')
            .forEach(function (el) { el.classList.add('inview'); });
    }

    // ── Parallax on featured project images ─────────
    var parallaxImgs = Array.prototype.slice.call(document.querySelectorAll('[data-ix-parallax]'));
    function parallax() {
        var vh = window.innerHeight;
        parallaxImgs.forEach(function (img) {
            var r = img.getBoundingClientRect();
            if (r.bottom < 0 || r.top > vh) return;
            var prog = (r.top + r.height / 2 - vh / 2) / vh;
            img.style.transform = 'translateY(' + (prog * -5).toFixed(2) + '%)';
        });
    }
    if (!reduce && parallaxImgs.length) {
        window.addEventListener('scroll', function () { requestAnimationFrame(parallax); }, { passive: true });
        window.addEventListener('resize', parallax);
        parallax();
    }

    // ── Recently Viewed rail — arrows + drag ────────
    var rail = document.getElementById('ixRecentRail');
    var prevBtn = document.getElementById('ixRailPrev');
    var nextBtn = document.getElementById('ixRailNext');

    if (rail && prevBtn && nextBtn) {
        function railStep() { return Math.round(rail.clientWidth * 0.7); }

        function syncRail() {
            var max = rail.scrollWidth - rail.clientWidth - 2;
            prevBtn.disabled = rail.scrollLeft <= 2;
            nextBtn.disabled = rail.scrollLeft >= max;
        }

        prevBtn.addEventListener('click', function () {
            rail.scrollBy({ left: -railStep(), behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', function () {
            rail.scrollBy({ left: railStep(), behavior: 'smooth' });
        });
        rail.addEventListener('scroll', function () { requestAnimationFrame(syncRail); }, { passive: true });
        window.addEventListener('resize', syncRail);
        syncRail();

        // drag-to-scroll
        var down = false, startX = 0, startLeft = 0, moved = false;
        rail.addEventListener('pointerdown', function (e) {
            down = true; moved = false; startX = e.clientX; startLeft = rail.scrollLeft;
            rail.classList.add('dragging'); rail.setPointerCapture(e.pointerId);
        });
        rail.addEventListener('pointermove', function (e) {
            if (!down) return;
            var dx = e.clientX - startX;
            if (Math.abs(dx) > 4) moved = true;
            rail.scrollLeft = startLeft - dx;
        });
        function endDrag() { down = false; rail.classList.remove('dragging'); }
        rail.addEventListener('pointerup', endDrag);
        rail.addEventListener('pointercancel', endDrag);
        rail.addEventListener('click', function (e) { if (moved) e.preventDefault(); }, true);
    }

    // ── Favorites toggle (event delegation) ─────────
    document.addEventListener('click', function (e) {
        var fav = e.target.closest('.ix-fav');
        if (!fav) return;
        e.preventDefault();
        e.stopPropagation();

        var propertyId = fav.getAttribute('data-property-id');
        if (!propertyId) {
            fav.classList.toggle('on');
            var icon = fav.querySelector('.material-icons');
            if (icon) icon.textContent = fav.classList.contains('on') ? 'favorite' : 'favorite_border';
            return;
        }

        // integrate with existing wishlist system
        if (typeof $ !== 'undefined' && $.fn) {
            var $heart = $('[data-id="' + propertyId + '"].add-to-wishlist');
            if ($heart.length) {
                $heart.trigger('click');
                fav.classList.toggle('on');
                var icon2 = fav.querySelector('.material-icons');
                if (icon2) icon2.textContent = fav.classList.contains('on') ? 'favorite' : 'favorite_border';
                return;
            }
        }

        fav.classList.toggle('on');
        var icon3 = fav.querySelector('.material-icons');
        if (icon3) icon3.textContent = fav.classList.contains('on') ? 'favorite' : 'favorite_border';
    });
})();
