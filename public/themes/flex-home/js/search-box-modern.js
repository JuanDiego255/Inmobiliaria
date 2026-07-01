(function ($) {
    'use strict';

    // -- Dropdown toggle --
    $(document).on('click', '.sbm-dropdown__trigger', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var dd = $(this).closest('.sbm-dropdown');
        var wasOpen = dd.hasClass('open');
        // Close all other dropdowns
        $('.sbm-dropdown.open').not(dd).removeClass('open');
        dd.toggleClass('open', !wasOpen);
        if (!wasOpen) {
            dd.find('.sbm-dropdown__search').first().focus();
        }
    });

    // Close on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.sbm-dropdown').length) {
            $('.sbm-dropdown.open').removeClass('open');
        }
    });

    // -- Search filter inside dropdown --
    $(document).on('input', '.sbm-dropdown__search', function () {
        var q = $(this).val().toLowerCase();
        $(this).closest('.sbm-dropdown__search-wrap').siblings('.sbm-dropdown__list').find('li').each(function () {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });

    // -- Category selection --
    $(document).on('click', '.sbm-dropdown[data-name="category_id"] .sbm-dropdown__list a', function (e) {
        e.preventDefault();
        var dd = $(this).closest('.sbm-dropdown');
        var val = $(this).data('value');
        var label = val ? $(this).text().trim() : dd.find('.sbm-dropdown__trigger .sbm-dropdown__label').data('default') || $(this).text().trim();

        dd.find('input[name="category_id"]').val(val);
        dd.find('.sbm-dropdown__label').text(label);
        dd.find('.sbm-dropdown__list a').removeClass('active');
        $(this).addClass('active');
        dd.removeClass('open');
    });

    // -- Type selection --
    $(document).on('click', '.sbm-dropdown[data-name="type"] .sbm-dropdown__list a', function (e) {
        e.preventDefault();
        var dd = $(this).closest('.sbm-dropdown');
        var val = $(this).data('value');
        var label = val ? $(this).text().trim() : dd.find('.sbm-dropdown__trigger .sbm-dropdown__label').data('default') || $(this).text().trim();

        $('#txttypesearch').val(val);
        dd.find('.sbm-dropdown__label').text(label);
        dd.find('.sbm-dropdown__list a').removeClass('active');
        $(this).addClass('active');
        dd.removeClass('open');
    });

    // -- Location: State selection → load cities --
    $(document).on('click', '#sbm-states-list a', function (e) {
        e.preventDefault();
        var stateId = $(this).data('value');
        var stateName = $(this).text().trim();
        var dd = $(this).closest('.sbm-dropdown');

        $('#sbm-states-list a').removeClass('active');
        $(this).addClass('active');
        dd.find('input[name="state_id"]').val(stateId);
        dd.find('input[name="city_id"]').val('');

        if (!stateId) {
            dd.find('.sbm-dropdown__label').text(dd.find('.sbm-dropdown__label').data('default') || stateName);
            $('#sbm-cities-list').html('<li class="sbm-dropdown__placeholder">' + ($('#sbm-cities-col').data('placeholder') || 'Select a state first') + '</li>');
            return;
        }

        dd.find('.sbm-dropdown__label').text(stateName);

        // Load cities for this state
        var citiesUrl = $('#sbm-cities-col').data('url');
        $('#sbm-cities-list').html('<li class="sbm-dropdown__placeholder"><i class="fas fa-spinner fa-spin"></i></li>');

        $.get(citiesUrl, { state_id: stateId }, function (response) {
            var items = response.data || response;
            var html = '<li><a href="#" data-value="">' + ($('#sbm-cities-col').data('all-label') || 'All') + '</a></li>';
            if (Array.isArray(items)) {
                items.forEach(function (city) {
                    var id = city.id || city.value;
                    var name = city.name || city.label || city.text;
                    if (id) {
                        html += '<li><a href="#" data-value="' + id + '">' + name + '</a></li>';
                    }
                });
            }
            $('#sbm-cities-list').html(html);
        }).fail(function () {
            $('#sbm-cities-list').html('<li class="sbm-dropdown__placeholder">Error</li>');
        });
    });

    // -- Location: City selection --
    $(document).on('click', '#sbm-cities-list a', function (e) {
        e.preventDefault();
        var cityId = $(this).data('value');
        var cityName = $(this).text().trim();
        var dd = $(this).closest('.sbm-dropdown');

        $('#sbm-cities-list a').removeClass('active');
        $(this).addClass('active');
        dd.find('input[name="city_id"]').val(cityId);

        if (cityId) {
            var stateName = $('#sbm-states-list a.active').text().trim();
            dd.find('.sbm-dropdown__label').text(cityName + ', ' + stateName);
        }

        dd.removeClass('open');
    });

    // -- Projects checkbox --
    $(document).on('change', '#search-in-projects', function () {
        var form = $('#frmhomesearch');
        if ($(this).is(':checked')) {
            form.attr('action', form.data('projects-url'));
            form.data('ajax-url', form.data('projects-ajax-url'));
            $('#txttypesearch').val('project');
        } else {
            form.attr('action', form.data('properties-url'));
            form.data('ajax-url', form.data('properties-ajax-url'));
            var activeType = $('.sbm-dropdown[data-name="type"] .sbm-dropdown__list a.active').data('value');
            $('#txttypesearch').val(activeType || 'sale');
        }
    });

    // Store default labels
    $(function () {
        $('.sbm-dropdown__label').each(function () {
            $(this).data('default', $(this).text().trim());
        });

        // Set initial active states from URL params
        var urlParams = new URLSearchParams(window.location.search);

        var catId = urlParams.get('category_id');
        if (catId) {
            var catLink = $('.sbm-dropdown[data-name="category_id"] a[data-value="' + catId + '"]');
            if (catLink.length) catLink.trigger('click');
        }

        var stateId = urlParams.get('state_id');
        if (stateId) {
            var stateLink = $('#sbm-states-list a[data-value="' + stateId + '"]');
            if (stateLink.length) stateLink.trigger('click');
        }
    });

})(jQuery);
