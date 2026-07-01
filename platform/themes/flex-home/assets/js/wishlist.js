;(function ($) {
    'use strict'
    let showSuccess = (message) => {
        window.showAlert('alert-success', message)
    }

    let __ = function (key) {
        window.trans = window.trans || {}

        return window.trans[key] !== 'undefined' && window.trans[key] ? window.trans[key] : key
    }

    window.showAlert = (messageType, message) => {
        if (messageType && message !== '') {
            let alertId = Math.floor(Math.random() * 1000)

            let html =
                `<div class="alert ${messageType} alert-dismissible" id="${alertId}">
                            <span class="close far fa-times" data-dismiss="alert" aria-label="close"></span>
                            <i class="far fa-` +
                (messageType === 'alert-success' ? 'check' : 'times') +
                ` message-icon"></i>
                            ${message}
                        </div>`

            $('#alert-container')
                .append(html)
                .ready(() => {
                    window.setTimeout(() => {
                        $(`#alert-container #${alertId}`).remove()
                    }, 6000)
                })
        }
    }

    function toggleWishlistIcon($el, filled) {
        // Material Icons version (pc-fav, ix-fav buttons)
        let $matIcon = $el.find('.material-icons')
        if ($matIcon.length) {
            $matIcon.text(filled ? 'favorite' : 'favorite_border')
            $el.toggleClass('on', filled)
            return
        }
        // FontAwesome version (legacy)
        let $faIcon = $el.find('i')
        if ($faIcon.length) {
            if (filled) {
                $faIcon.removeClass('far fa-heart').addClass('fas fa-heart')
            } else {
                $faIcon.removeClass('fas fa-heart').addClass('far fa-heart')
            }
        }
    }

    $(document).ready(function () {
        setWishListCount()

        // Unified click handler for all wishlist buttons
        $(document).on('click', '.add-to-wishlist, .pc-fav, .ix-fav', function (e) {
            e.preventDefault()
            e.stopPropagation()

            let cookieName = 'wishlist'
            let $btn = $(this)
            let propertyId = $btn.data('id') || $btn.data('property-id')
            let wishCookies = decodeURIComponent(getCookie(cookieName))
            let arrWList = []

            if (propertyId != null && propertyId != 0 && propertyId != undefined) {
                if (wishCookies == undefined || wishCookies == null || wishCookies == '') {
                    let item = { id: propertyId }
                    arrWList.push(item)
                    toggleWishlistIcon($btn, true)
                    showSuccess(__('Added to wishlist successfully!'))
                    setCookie(cookieName, JSON.stringify(arrWList), 60)
                } else {
                    let item = { id: propertyId }
                    arrWList = JSON.parse(wishCookies)
                    let index = arrWList
                        .map(function (e) {
                            return e.id
                        })
                        .indexOf(item.id)

                    if (index === -1) {
                        arrWList.push(item)
                        clearCookies(cookieName)
                        setCookie(cookieName, JSON.stringify(arrWList), 60)
                        toggleWishlistIcon($btn, true)
                        showSuccess(__('Added to wishlist successfully!'))
                    } else {
                        arrWList.splice(index, 1)
                        clearCookies(cookieName)
                        setCookie(cookieName, JSON.stringify(arrWList), 60)
                        toggleWishlistIcon($btn, false)
                        showSuccess(__('Removed from wishlist successfully!'))
                    }
                }
            }

            let cookieVal = getCookie(cookieName)
            if (cookieVal) {
                let countWishlist = JSON.parse(cookieVal).length
                $('.wishlist-count').text(countWishlist)
            }
            setWishListCount()
        })

        $(document).on('click', '.remove-from-wishlist', function (e) {
            e.preventDefault()

            let cookieName = 'wishlist'
            let propertyId = $(this).data('id')
            let wishCookies = decodeURIComponent(getCookie(cookieName))
            let arrWList = []

            if (propertyId != null && propertyId != 0 && propertyId != undefined) {
                let item = { id: propertyId }
                arrWList = JSON.parse(wishCookies)
                let index = arrWList
                    .map(function (e) {
                        return e.id
                    })
                    .indexOf(item.id)

                if (index != -1) {
                    arrWList.splice(index, 1)
                    clearCookies(cookieName)
                    setCookie(cookieName, JSON.stringify(arrWList), 60)

                    showSuccess(__('Removed from wishlist successfully!'))
                    $(`.wishlist-page .item[data-id=${propertyId}]`).closest('div').remove()
                }
            }

            let cookieVal = getCookie(cookieName)
            if (cookieVal) {
                let countWishlist = JSON.parse(cookieVal).length
                $('.wishlist-count').text(countWishlist)
            }
            setWishListCount()
        })

        function setWishListCount() {
            let cookieName = 'wishlist'
            let wishListCookies = decodeURIComponent(getCookie(cookieName))

            if (wishListCookies != null && wishListCookies != undefined && !!wishListCookies) {
                let arrList = JSON.parse(wishListCookies)
                let countWishlist = arrList.length

                $('.wishlist-count').text(countWishlist)
                if (countWishlist > 0) {
                    $.each(arrList, function (key, value) {
                        if (value != null) {
                            // FontAwesome legacy
                            $(document).find(`.add-to-wishlist[data-id=${value.id}] i`).addClass('fas fa-heart')
                            // Material Icons — pc-fav
                            $(document).find(`.pc-fav[data-id=${value.id}]`).each(function() {
                                $(this).addClass('on').find('.material-icons').text('favorite')
                            })
                            // Material Icons — ix-fav
                            $(document).find(`.ix-fav[data-property-id=${value.id}]`).each(function() {
                                $(this).addClass('on').find('.material-icons').text('favorite')
                            })
                        }
                    })
                }
            }
        }

        function setCookie(cname, cvalue, exdays) {
            let d = new Date()
            let siteUrl = window.siteUrl

            if (!siteUrl.includes(window.location.protocol)) {
                siteUrl = window.location.protocol + siteUrl
            }

            let url = new URL(siteUrl)
            d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000)
            let expires = 'expires=' + d.toUTCString()
            document.cookie = cname + '=' + cvalue + '; ' + expires + '; path=/' + '; domain=' + url.hostname
        }

        function getCookie(cname) {
            let name = cname + '='
            let ca = document.cookie.split(';')
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i]
                while (c.charAt(0) == ' ') {
                    c = c.substring(1)
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length)
                }
            }
            return ''
        }

        function clearCookies(name) {
            let siteUrl = window.siteUrl

            if (!siteUrl.includes(window.location.protocol)) {
                siteUrl = window.location.protocol + siteUrl
            }

            let url = new URL(siteUrl)
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/' + '; domain=' + url.hostname
        }

        function checkWishlistInElement($el) {
            let parseCookie = JSON.parse(getCookie('wishlist') || '{}')
            if (parseCookie && parseCookie.length) {
                // FontAwesome legacy
                $el.find('.add-to-wishlist').map(function () {
                    let wlId = $(this).data('id')
                    let exists = parseCookie.some((x) => x.id === wlId)
                    if (exists) {
                        $(this).find('i').addClass('fas')
                    }
                })
                // Material Icons
                $el.find('.pc-fav, .ix-fav').map(function () {
                    let wlId = $(this).data('id') || $(this).data('property-id')
                    let exists = parseCookie.some((x) => x.id === wlId)
                    if (exists) {
                        $(this).addClass('on').find('.material-icons').text('favorite')
                    }
                })
            }
        }

        window.wishlishInElement = checkWishlistInElement
    })
})(jQuery)
