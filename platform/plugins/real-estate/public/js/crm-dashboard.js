(function () {
    'use strict';

    function init() {
        // Click on lead rows opens detail modal
        document.querySelectorAll('.crm-clickable-row').forEach(function (row) {
            row.addEventListener('click', function () {
                var leadId = row.dataset.leadId;
                if (leadId && typeof window.CRM_openLeadDetail === 'function') {
                    window.CRM_openLeadDetail(leadId);
                }
            });
        });

        // Click on lead name in timeline
        document.querySelectorAll('.crm-clickable-lead').forEach(function (el) {
            el.addEventListener('click', function () {
                var leadId = el.dataset.leadId;
                if (leadId && typeof window.CRM_openLeadDetail === 'function') {
                    window.CRM_openLeadDetail(leadId);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
