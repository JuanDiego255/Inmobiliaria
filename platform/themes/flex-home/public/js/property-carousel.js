(function () {
    'use strict';

    var pinWrap = document.getElementById('pcPinWrap');
    var track = document.getElementById('pcTrack');
    var fill = document.getElementById('pcProgFill');
    var counter = document.getElementById('pcProgNum');
    var totalEl = document.getElementById('pcProgTotal');
    var noResults = document.getElementById('pcNoResults');

    if (!pinWrap || !track) return;

    var inner = pinWrap.querySelector('.pc-pin-inner');
    var allCards = Array.prototype.slice.call(track.querySelectorAll('.pc-card'));
    var maxX = 0, target = 0, current = 0;
    var speed = 1.35;

    function getVisibleCards() {
        return allCards.filter(function (c) { return !c.classList.contains('pc-hidden'); });
    }

    function layout() {
        var visible = getVisibleCards();
        var visibleCount = visible.length;

        if (totalEl) totalEl.textContent = String(visibleCount).padStart(2, '0');

        if (visibleCount === 0) {
            pinWrap.style.height = 'auto';
            track.style.display = 'none';
            if (noResults) noResults.style.display = '';
            pinWrap.querySelector('.pc-prog').style.display = 'none';
            return;
        }

        track.style.display = '';
        if (noResults) noResults.style.display = 'none';
        pinWrap.querySelector('.pc-prog').style.display = '';

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

    // ── Client-side filtering ──────────────────────
    var filterForm = document.getElementById('pc-filter-form');
    if (!filterForm) return;

    function applyFilters() {
        var formData = new FormData(filterForm);
        var catId = formData.get('category_id') || '';
        var type = formData.get('type') || '';
        var stateId = formData.get('state_id') || '';
        var cityId = formData.get('city_id') || '';
        var bedroom = formData.get('bedroom') || '';
        var bathroom = formData.get('bathroom') || '';
        var minPrice = parseFloat(formData.get('min_price')) || 0;
        var maxPrice = parseFloat(formData.get('max_price')) || 0;
        var keyword = (formData.get('k') || '').toLowerCase().trim();

        // Reset scroll position
        current = 0;
        target = 0;
        track.style.transform = 'translate3d(0,0,0)';

        allCards.forEach(function (card) {
            var show = true;

            if (catId && card.dataset.category) {
                var cats = card.dataset.category.split(',');
                if (cats.indexOf(catId) === -1) show = false;
            }

            if (type && card.dataset.type !== type) show = false;

            if (stateId && card.dataset.state !== stateId) show = false;

            if (cityId && card.dataset.city !== cityId) show = false;

            if (bedroom) {
                var cardBeds = parseInt(card.dataset.bedrooms) || 0;
                var filterBeds = parseInt(bedroom);
                if (filterBeds >= 5) {
                    if (cardBeds < 5) show = false;
                } else {
                    if (cardBeds < filterBeds) show = false;
                }
            }

            if (bathroom) {
                var cardBaths = parseInt(card.dataset.bathrooms) || 0;
                var filterBaths = parseInt(bathroom);
                if (filterBaths >= 5) {
                    if (cardBaths < 5) show = false;
                } else {
                    if (cardBaths < filterBaths) show = false;
                }
            }

            var cardPrice = parseFloat(card.dataset.price) || 0;
            if (minPrice > 0 && cardPrice < minPrice) show = false;
            if (maxPrice > 0 && cardPrice > maxPrice) show = false;

            if (keyword) {
                var cardText = card.textContent.toLowerCase();
                if (cardText.indexOf(keyword) === -1) show = false;
            }

            card.classList.toggle('pc-hidden', !show);
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
                card.classList.remove('pc-hidden');
            });
            current = 0;
            target = 0;
            track.style.transform = 'translate3d(0,0,0)';
            layout();
        }, 10);
    });
})();
