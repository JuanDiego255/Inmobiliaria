import Botble from './utils'

require('./form')
require('./avatar')

$(document).ready(() => {
    if (window.noticeMessages) {
        window.noticeMessages.forEach((message) => {
            Botble.showNotice(
                message.type,
                message.message,
                message.type === 'error'
                    ? window.trans && window.trans.error
                        ? window.trans.error
                        : 'Error!'
                    : window.trans && window.trans.success
                    ? window.trans.success
                    : 'Success!'
            )
        })
    }

    $(document).on('click', '.button-renew', (event) => {
        event.preventDefault()
        let _self = $(event.currentTarget)

        $('.button-confirm-renew')
            .data('section', _self.prop('href'))
            .data('parent-table', _self.closest('.table').prop('id'))
        $('.modal-confirm-renew').modal('show')
    })

    $('.button-confirm-renew').on('click', (event) => {
        event.preventDefault()
        let _self = $(event.currentTarget)

        let url = _self.data('section')

        _self.addClass('button-loading')

        $.ajax({
            url: url,
            type: 'POST',
            success: (data) => {
                if (data.error) {
                    Botble.showError(data.message)
                } else {
                    window.LaravelDataTables[_self.data('parent-table')]
                        .row($('a[data-section="' + url + '"]').closest('tr'))
                        .remove()
                        .draw()
                    Botble.showSuccess(data.message)
                }

                _self.closest('.modal').modal('hide')
                _self.removeClass('button-loading')
            },
            error: (data) => {
                Botble.handleError(data)
                _self.removeClass('button-loading')
            },
        })
    })

    $(document).on('click', '.btn_remove_image', (event) => {
        event.preventDefault()
        $(event.currentTarget).closest('.image-box').find('.preview-image-wrapper').hide()
        $(event.currentTarget).closest('.image-box').find('.image-data').val('')
    })

    const refreshCoupon = (url) => {
        $.ajax({
            url: url,
            type: 'GET',
            success: ({ error, message, data }) => {
                if (error) {
                    Botble.showError(message)

                    return
                }

                $('.order-detail-box').html(data)

                Botble.showSuccess(message)
            },
            error: (error) => {
                Botble.handleError(error)
            },
        })
    }

    $(document)
        .on('click', '.toggle-coupon-form', () => $(document).find('.coupon-form').toggle('fast'))
        .on('click', '.apply-coupon-code', (e) => {
            e.preventDefault()

            const $button = $(e.currentTarget)

            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                data: {
                    coupon_code: $button.closest('form').find('input[name="coupon_code"]').val(),
                },
                beforeSend: () => {
                    $button.addClass('button-loading')
                },
                success: ({ error, message }) => {
                    if (error) {
                        Botble.showError(message)

                        return
                    }

                    Botble.showSuccess(message)

                    const refreshUrl = $('.order-detail-box').data('refresh-url')
                    refreshCoupon(refreshUrl)
                },
                error: (error) => {
                    Botble.handleError(error)
                },
                complete: () => {
                    $button.removeClass('button-loading')
                },
            })
        })
        .on('click', '.remove-coupon-code', (e) => {
            e.preventDefault()

            const $button = $(e.currentTarget)

            $.ajax({
                url: $button.data('url'),
                type: 'POST',
                beforeSend: () => {
                    $button.addClass('button-loading')
                },
                success: ({ message, error }) => {
                    if (error) {
                        Botble.showError(message)

                        return
                    }

                    Botble.showSuccess(message)

                    const refreshUrl = $('.order-detail-box').data('refresh-url')
                    refreshCoupon(refreshUrl)
                },
                error: (error) => {
                    Botble.handleError(error)
                },
                complete: () => {
                    $button.removeClass('button-loading')
                },
            })
        })
})
