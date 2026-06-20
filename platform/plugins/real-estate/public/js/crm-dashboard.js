(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function fillPipe(animate) {
        var fills = document.querySelectorAll('.cd-pl-fill');
        if (!fills.length) return;
        var max = 1;
        fills.forEach(function (f) { var v = +f.dataset.v; if (v > max) max = v; });
        fills.forEach(function (f) {
            var pct = (+f.dataset.v / max) * 100;
            if (animate) {
                requestAnimationFrame(function () { f.style.width = pct + '%'; });
            } else {
                f.style.width = pct + '%';
            }
        });
    }

    function initReveal() {
        if (!reduce && 'IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) {
                    if (en.isIntersecting) {
                        en.target.classList.add('cd-inview');
                        if (en.target.querySelector('#cd-pipe')) fillPipe(true);
                        io.unobserve(en.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
            document.querySelectorAll('.cd-reveal-up').forEach(function (el) { io.observe(el); });
        } else {
            document.querySelectorAll('.cd-reveal-up').forEach(function (el) { el.classList.add('cd-inview'); });
            fillPipe(false);
        }
    }

    function initClickables() {
        document.querySelectorAll('.crm-clickable-row').forEach(function (row) {
            row.addEventListener('click', function () {
                var leadId = row.dataset.leadId;
                if (leadId && typeof window.CRM_openLeadDetail === 'function') {
                    window.CRM_openLeadDetail(leadId);
                }
            });
        });

        document.querySelectorAll('.crm-clickable-lead').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.stopPropagation();
                var leadId = el.dataset.leadId;
                if (leadId && typeof window.CRM_openLeadDetail === 'function') {
                    window.CRM_openLeadDetail(leadId);
                }
            });
        });
    }

    function init() {
        initReveal();
        initClickables();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
