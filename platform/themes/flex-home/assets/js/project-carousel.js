(function () {
    'use strict';

    var pinWrap = document.getElementById('pjPinWrap');
    var track = document.getElementById('pjTrack');
    var fill = document.getElementById('pjProgFill');
    var counter = document.getElementById('pjProgNum');
    var totalEl = document.getElementById('pjProgTotal');
    var noResults = document.getElementById('pjNoResults');

    if (!pinWrap || !track) return;

    var inner = pinWrap.querySelector('.pj-pin-inner');
    var allCards = Array.prototype.slice.call(track.querySelectorAll('.pj-card'));
    var maxX = 0, target = 0, current = 0;
    var speed = 1.35;

    function getVisibleCards() {
        return allCards.filter(function (c) { return !c.classList.contains('pj-hidden'); });
    }

    function layout() {
        var visible = getVisibleCards();
        var visibleCount = visible.length;

        if (totalEl) totalEl.textContent = String(visibleCount).padStart(2, '0');

        if (visibleCount === 0) {
            pinWrap.style.height = 'auto';
            track.style.display = 'none';
            if (noResults) noResults.style.display = '';
            var prog = pinWrap.querySelector('.pj-prog');
            if (prog) prog.style.display = 'none';
            return;
        }

        track.style.display = '';
        if (noResults) noResults.style.display = 'none';
        var prog = pinWrap.querySelector('.pj-prog');
        if (prog) prog.style.display = '';

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
        var visibleCount = getVisibleCards().length;
        var idx = Math.min(visibleCount, Math.max(1, Math.round(p * (visibleCount - 1)) + 1));
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

    // ── Client-side filtering ──────────────────────
    var filterForm = document.getElementById('pj-filter-form');
    if (!filterForm) return;

    function applyFilters() {
        var formData = new FormData(filterForm);
        var catId = formData.get('category_id') || '';
        var stateId = formData.get('state_id') || '';
        var cityId = formData.get('city_id') || '';
        var keyword = (formData.get('k') || '').toLowerCase().trim();

        current = 0;
        target = 0;
        track.style.transform = 'translate3d(0,0,0)';

        allCards.forEach(function (card) {
            var show = true;

            if (catId && card.dataset.category !== catId) show = false;
            if (stateId && card.dataset.state !== stateId) show = false;
            if (cityId && card.dataset.city !== cityId) show = false;

            if (keyword) {
                var cardText = card.textContent.toLowerCase();
                if (cardText.indexOf(keyword) === -1) show = false;
            }

            card.classList.toggle('pj-hidden', !show);
        });

        layout();
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        applyFilters();
    });

    filterForm.addEventListener('reset', function () {
        setTimeout(function () {
            allCards.forEach(function (card) {
                card.classList.remove('pj-hidden');
            });
            current = 0;
            target = 0;
            track.style.transform = 'translate3d(0,0,0)';
            layout();
        }, 10);
    });

    // Sort controls
    var perPageSelect = document.getElementById('pj-per-page');
    var sortBySelect = document.getElementById('pj-sort-by');

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            if (filterForm) filterForm.submit();
        });
    }

    if (sortBySelect) {
        sortBySelect.addEventListener('change', function () {
            var cards = getVisibleCards();
            var sortVal = this.value;
            if (!sortVal) return;

            cards.sort(function (a, b) {
                switch (sortVal) {
                    case 'price_asc':
                        return (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0);
                    case 'price_desc':
                        return (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0);
                    case 'name_asc':
                        return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                    case 'name_desc':
                        return (b.dataset.name || '').localeCompare(a.dataset.name || '');
                    case 'date_desc':
                        return (b.dataset.date || '').localeCompare(a.dataset.date || '');
                    case 'date_asc':
                        return (a.dataset.date || '').localeCompare(b.dataset.date || '');
                    default:
                        return 0;
                }
            });

            cards.forEach(function (card) {
                track.appendChild(card);
            });

            current = 0;
            target = 0;
            track.style.transform = 'translate3d(0,0,0)';
            layout();
        });
    }
})();
