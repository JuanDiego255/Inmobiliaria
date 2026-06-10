(function () {
    'use strict';

    // ── Lightbox ────────────────────────────────────────────────
    var lb = document.getElementById('pdLightbox');
    var lbImg = document.getElementById('pdLbImg');
    var lbCount = document.getElementById('pdLbCount');
    var lbThumbs = document.getElementById('pdLbThumbs');
    var gallery = document.getElementById('pdGallery');

    if (!lb || !lbImg) return;

    var images = [];
    var thumbEls = lbThumbs ? lbThumbs.querySelectorAll('img') : [];
    for (var i = 0; i < thumbEls.length; i++) {
        images.push(thumbEls[i].getAttribute('data-full'));
    }

    var lbIdx = 0;

    function lbShow(idx) {
        lbIdx = (idx + images.length) % images.length;
        lbImg.src = images[lbIdx];
        if (lbCount) lbCount.textContent = (lbIdx + 1) + ' / ' + images.length;
        var allThumbs = lbThumbs ? lbThumbs.querySelectorAll('img') : [];
        for (var j = 0; j < allThumbs.length; j++) {
            allThumbs[j].classList.toggle('pd-on', j === lbIdx);
        }
    }

    function lbOpen(idx) {
        lbShow(idx);
        lb.classList.add('pd-open');
        document.body.style.overflow = 'hidden';
    }

    function lbClose() {
        lb.classList.remove('pd-open');
        document.body.style.overflow = '';
    }

    if (gallery) {
        gallery.addEventListener('click', function (e) {
            var more = e.target.closest('.pd-g-more');
            if (more) { lbOpen(0); return; }
            var cell = e.target.closest('.pd-g-cell');
            if (cell) lbOpen(parseInt(cell.dataset.i) || 0);
        });
    }

    if (lbThumbs) {
        lbThumbs.addEventListener('click', function (e) {
            var t = e.target.closest('[data-i]');
            if (t) lbShow(parseInt(t.dataset.i) || 0);
        });
    }

    var closeBtn = document.getElementById('pdLbClose');
    if (closeBtn) closeBtn.addEventListener('click', lbClose);

    var prevBtn = document.getElementById('pdLbPrev');
    if (prevBtn) prevBtn.addEventListener('click', function () { lbShow(lbIdx - 1); });

    var nextBtn = document.getElementById('pdLbNext');
    if (nextBtn) nextBtn.addEventListener('click', function () { lbShow(lbIdx + 1); });

    lb.addEventListener('click', function (e) {
        if (e.target === lb) lbClose();
    });

    document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('pd-open')) return;
        if (e.key === 'Escape') lbClose();
        if (e.key === 'ArrowLeft') lbShow(lbIdx - 1);
        if (e.key === 'ArrowRight') lbShow(lbIdx + 1);
    });

    // ── Star input (write review) ───────────────────────────────
    var starInput = document.getElementById('pdStarInput');
    var starSelect = document.getElementById('select-star');
    if (starInput) {
        var chosen = starSelect ? parseInt(starSelect.value) || 5 : 5;
        var starIcons = starInput.querySelectorAll('.material-icons');

        function paintStars(n) {
            for (var k = 0; k < starIcons.length; k++) {
                starIcons[k].classList.toggle('pd-on', k < n);
            }
        }

        starInput.addEventListener('mouseover', function (e) {
            var s = e.target.closest('[data-v]');
            if (s) paintStars(parseInt(s.dataset.v));
        });

        starInput.addEventListener('mouseleave', function () {
            paintStars(chosen);
        });

        starInput.addEventListener('click', function (e) {
            var s = e.target.closest('[data-v]');
            if (s) {
                chosen = parseInt(s.dataset.v);
                paintStars(chosen);
                if (starSelect) {
                    starSelect.value = chosen;
                    if (typeof jQuery !== 'undefined' && jQuery.fn.barrating) {
                        jQuery('#select-star').barrating('set', chosen);
                    }
                }
            }
        });

        paintStars(chosen);
    }

    // ── Copy link ───────────────────────────────────────────────
    var copyBtn = document.getElementById('pdCopyLink');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(location.href);
            }
        });
    }

    // ── Back to top ─────────────────────────────────────────────
    var toTop = document.getElementById('pdToTop');
    if (toTop) {
        window.addEventListener('scroll', function () {
            toTop.classList.toggle('pd-show', window.scrollY > 600);
        }, { passive: true });

        toTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
