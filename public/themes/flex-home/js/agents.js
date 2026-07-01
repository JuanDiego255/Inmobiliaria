(function () {
    'use strict';

    /* ------------------------------------------------------------------ */
    /*  1. IntersectionObserver  reveal-on-scroll                         */
    /* ------------------------------------------------------------------ */

    var prefersReducedMotion =
        window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initReveal() {
        var revealEls    = document.querySelectorAll('.ag-reveal-up');
        var agentCards   = document.querySelectorAll('.ag-agents-grid .ag-agent-card');
        var propCards    = document.querySelectorAll('.ag-props-grid .ix-prop-card');

        /* If user prefers reduced motion, just mark everything visible */
        if (prefersReducedMotion) {
            revealEls.forEach(function (el)  { el.classList.add('inview'); });
            agentCards.forEach(function (el)  { el.classList.add('inview'); });
            propCards.forEach(function (el)   { el.classList.add('inview'); });
            return;
        }

        var observerOptions = {
            threshold:  0.15,
            rootMargin: '0px 0px -6% 0px'
        };

        /* --- generic reveal-up elements --- */
        if (revealEls.length) {
            var revealObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('inview');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            revealEls.forEach(function (el) {
                revealObserver.observe(el);
            });
        }

        /* --- agent cards (staggered 4-column) --- */
        if (agentCards.length) {
            var agentObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('inview');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            agentCards.forEach(function (el, index) {
                el.style.transitionDelay = (index % 4) * 0.07 + 0.04 + 's';
                agentObserver.observe(el);
            });
        }

        /* --- property cards (staggered 3-column) --- */
        if (propCards.length) {
            var propObserver = new IntersectionObserver(function (entries, observer) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('inview');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            propCards.forEach(function (el, index) {
                el.style.transitionDelay = (index % 3) * 0.08 + 0.04 + 's';
                propObserver.observe(el);
            });
        }
    }

    /* ------------------------------------------------------------------ */
    /*  2. Client-side agent search  (listing page only)                  */
    /* ------------------------------------------------------------------ */

    function initAgentSearch() {
        var searchInput = document.getElementById('agAgentSearch');
        if (!searchInput) {
            return;
        }

        var cards      = document.querySelectorAll('.ag-agent-card');
        var emptyState = document.querySelector('.ag-empty');
        var grid       = document.querySelector('.ag-agents-grid');
        var countEl    = document.getElementById('agAgentCount');

        if (!cards.length) {
            return;
        }

        searchInput.addEventListener('input', function () {
            var query   = this.value.toLowerCase().trim();
            var visible = 0;

            cards.forEach(function (card) {
                var name    = (card.getAttribute('data-name') || '').toLowerCase();
                var matches = !query || name.includes(query);

                card.style.display = matches ? '' : 'none';
                if (matches) {
                    visible++;
                }
            });

            /* Toggle empty-state message */
            if (emptyState) {
                emptyState.style.display = visible === 0 ? 'block' : 'none';
            }

            /* Toggle grid visibility (hide when zero results) */
            if (grid) {
                grid.style.display = visible === 0 ? 'none' : '';
            }

            /* Update visible count */
            if (countEl) {
                countEl.textContent = visible;
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Boot                                                              */
    /* ------------------------------------------------------------------ */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initReveal();
            initAgentSearch();
        });
    } else {
        initReveal();
        initAgentSearch();
    }
})();
