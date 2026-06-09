<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=1" name="viewport"/>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {!! BaseHelper::googleFonts('https://fonts.googleapis.com/css2?family=' . urlencode(theme_option('primary_font', 'Nunito Sans')) . ':wght@300;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap') !!}
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        :root {
            --primary-color: {{ theme_option('primary_color', '#1d5f6f') }};
            --primary-color-rgb: {{ BaseHelper::hexToRgba(theme_option('primary_color', '#1d5f6f'), 0.8) }};
            --primary-color-hover: {{ theme_option('primary_color_hover', '#063a5d') }};
            --primary-font: '{{ theme_option('primary_font', 'Nunito Sans') }}';
            --header-bg: {{ theme_option('header_bg_color', '#0f1e33') }};
        }
    </style>

    {!! Theme::header() !!}
</head>
<body @if (BaseHelper::siteLanguageDirection() == 'rtl') dir="rtl" @endif>
{!! apply_filters(THEME_FRONT_BODY, null) !!}
<div id="alert-container"></div>

{{-- ============ NEW HEADER ============ --}}
@php
    $socialLinks = json_decode(theme_option('social_links'), true) ?: [];
    $hasSocialLinks = false;
    foreach ($socialLinks as $sl) {
        if (count($sl) == 3 && $sl[1]['value'] && $sl[2]['value']) { $hasSocialLinks = true; break; }
    }

    // Get menu nodes via Botble's language-aware query system
    $eliteMenuNodes = collect();
    $menuQuery = \Botble\Menu\Models\Menu::query()
        ->wherePublished()
        ->with(['menuNodes', 'menuNodes.child', 'locations']);
    $menus = \Botble\Base\Supports\RepositoryHelper::applyBeforeExecuteQuery($menuQuery, new \Botble\Menu\Models\Menu())->get();
    foreach ($menus as $menu) {
        if ($menu->locations->pluck('location')->contains('main-menu')) {
            $eliteMenuNodes = $menu->menuNodes
                ->where('parent_id', 0)
                ->sortBy('position')
                ->values();
            break;
        }
    }
    $leftMenuItems = $eliteMenuNodes;
    $allMenuItems = $eliteMenuNodes;
@endphp

<header class="elite-header" id="elite-header">
    <div class="elite-header__inner">
        {{-- Left: Menu items (desktop) --}}
        <nav class="elite-header__nav elite-header__nav--left d-none d-lg-flex">
            @foreach($leftMenuItems as $item)
                <a href="{{ url($item->url) }}" class="elite-header__link @if($item->active) active @endif">{{ $item->title }}</a>
            @endforeach
        </nav>

        {{-- Center: Logo with animation --}}
        <div class="elite-header__logo-wrap">
            <a href="{{ route('public.index') }}" class="elite-header__logo">
                <div class="elite-logo-anim">
                    @if (theme_option('logo'))
                        <img src="{{ RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_name') }}" class="elite-logo-anim__img">
                    @else
                        <span class="elite-logo-anim__text">{{ theme_option('site_name', 'Logo') }}</span>
                    @endif
                </div>
            </a>
        </div>

        {{-- Right: Social icons + hamburger --}}
        <div class="elite-header__right">
            @if ($hasSocialLinks)
                <div class="elite-header__socials d-none d-lg-flex">
                    @foreach($socialLinks as $socialLink)
                        @if (count($socialLink) == 3 && $socialLink[1]['value'] && $socialLink[2]['value'])
                            <a href="{{ $socialLink[2]['value'] }}" title="{{ $socialLink[0]['value'] }}" target="_blank" class="elite-header__social-icon">
                                <i class="{{ $socialLink[1]['value'] }}"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Hamburger --}}
            <button class="elite-hamburger" id="elite-hamburger" type="button" aria-label="Menu">
                <span class="elite-hamburger__line"></span>
                <span class="elite-hamburger__line"></span>
            </button>
        </div>
    </div>

    {{-- Tapered HR lines BELOW the navbar --}}
    <div class="elite-header__hr-wrap d-none d-lg-flex">
        <div class="elite-header__taper-line elite-header__taper-line--left"></div>
        <div class="elite-header__taper-line elite-header__taper-line--right"></div>
    </div>
</header>

{{-- ============ FULLSCREEN OVERLAY MENU ============ --}}
<div class="elite-overlay" id="elite-overlay">
    <div class="elite-overlay__content">
        <nav class="elite-overlay__nav">
            @foreach($allMenuItems as $idx => $item)
                <a href="{{ url($item->url) }}" class="elite-overlay__link" style="animation-delay: {{ $idx * 0.06 }}s">
                    {{ $item->title }}
                </a>
            @endforeach
        </nav>

        <div class="elite-overlay__bottom">
            @if ($hasSocialLinks)
                <div class="elite-overlay__socials">
                    @foreach($socialLinks as $socialLink)
                        @if (count($socialLink) == 3 && $socialLink[1]['value'] && $socialLink[2]['value'])
                            <a href="{{ $socialLink[2]['value'] }}" title="{{ $socialLink[0]['value'] }}" target="_blank">
                                <i class="{{ $socialLink[1]['value'] }}"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            @if ($email = theme_option('email'))
                <a href="mailto:{{ $email }}" class="elite-overlay__email">{{ $email }}</a>
            @endif

            @if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled())
                <div class="elite-overlay__auth">
                    @if (auth('account')->check())
                        <a href="{{ route('public.account.dashboard') }}"><i class="fas fa-user"></i> {{ auth('account')->user()->name }}</a>
                        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}</a>
                    @else
                        <a href="{{ route('public.account.login') }}"><i class="fas fa-sign-in-alt"></i> {{ __('Login') }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@if (is_plugin_active('real-estate') && RealEstateHelper::isLoginEnabled() && auth('account')->check())
    <form id="logout-form" action="{{ route('public.account.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
@endif

<style>
/* ================================================
   ELITE HEADER — Centered Logo + Hamburger Overlay
   ================================================ */

/* 1. Hide admin bar + old header elements — aggressively */
#admin-bar,
.admin-bar-container,
.admin-bar,
[class*="admin-bar"],
.bravo_topbar,
header.topmenu {
    display: none !important;
    height: 0 !important;
    min-height: 0 !important;
    max-height: 0 !important;
    overflow: hidden !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    visibility: hidden !important;
}

body.show-admin-bar,
body[class*="admin-bar"] {
    padding-top: 0 !important;
    margin-top: 0 !important;
}

/* Hide admin bar injected stylesheet */
link[href*="admin-bar.css"] {
    display: none;
}

/* 2. Header overlays the banner — NO body padding */
.elite-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 9999;
    padding: 0 40px;
    transition: background 0.4s, box-shadow 0.4s, position 0s;
}

.elite-header--scrolled {
    position: fixed;
    background: rgba(15, 30, 51, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0,0,0,0.2);
}

.elite-header__inner {
    display: flex;
    align-items: center;
    justify-content: center;
    max-width: 1600px;
    margin: 0 auto;
    height: 80px;
    gap: 30px;
}

/* Nav links */
.elite-header__nav {
    display: flex;
    align-items: center;
    gap: 28px;
}

.elite-header__nav--left {
    justify-content: flex-end;
    flex: 1;
}

.elite-header__link {
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 1.8px;
    text-transform: uppercase;
    transition: color 0.2s;
    position: relative;
    white-space: nowrap;
}

.elite-header__link:hover,
.elite-header__link.active {
    color: #fff;
    text-decoration: none;
}

.elite-header__link::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 0;
    height: 1px;
    background: #fff;
    transition: width 0.3s;
}

.elite-header__link:hover::after,
.elite-header__link.active::after {
    width: 100%;
}

/* 4. Tapered HR lines — BELOW the navbar, full width */
.elite-header__hr-wrap {
    display: flex;
    width: 100%;
    max-width: 1600px;
    margin: -6px auto 0;
    padding: 0 0;
    gap: 0;
}

.elite-header__taper-line {
    flex: 1;
    height: 3px;
    opacity: 0;
    transition: opacity 0.8s ease 0.2s;
}

.elite-header.loaded .elite-header__taper-line {
    opacity: 1;
}

.elite-header__taper-line--left {
    background: linear-gradient(to right, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0) 100%);
}

.elite-header__taper-line--right {
    background: linear-gradient(to left, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0) 100%);
}

/* Logo */
.elite-header__logo-wrap {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.elite-header__logo {
    text-decoration: none;
}

.elite-logo-anim {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.elite-logo-anim__img {
    height: 48px;
    width: auto;
    filter: brightness(0) invert(1);
    opacity: 0;
    transform: scale(0.8);
    transition: opacity 0.6s ease 0.15s, transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s;
}

.elite-header.loaded .elite-logo-anim__img {
    opacity: 1;
    transform: scale(1);
}

.elite-logo-anim__text {
    color: #fff;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
}

/* Hover: gentle glow */
.elite-header__logo:hover .elite-logo-anim__img {
    animation: eliteLogoGlow 0.8s ease;
}

@keyframes eliteLogoGlow {
    0%   { filter: brightness(0) invert(1) drop-shadow(0 0 0 transparent); }
    50%  { filter: brightness(0) invert(1) drop-shadow(0 0 12px rgba(255,255,255,0.5)); }
    100% { filter: brightness(0) invert(1) drop-shadow(0 0 0 transparent); }
}

/* Right section */
.elite-header__right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 20px;
    flex: 1;
}

.elite-header__socials {
    display: flex;
    align-items: center;
    gap: 16px;
}

.elite-header__social-icon {
    color: rgba(255,255,255,0.6);
    font-size: 14px;
    transition: color 0.2s, transform 0.2s;
    text-decoration: none;
}

.elite-header__social-icon:hover {
    color: #fff;
    transform: translateY(-2px);
    text-decoration: none;
}

/* Hamburger */
.elite-hamburger {
    width: 44px;
    height: 44px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    z-index: 10001;
    position: relative;
}

.elite-hamburger__line {
    display: block;
    width: 28px;
    height: 2px;
    background: #fff;
    border-radius: 2px;
    transition: transform 0.4s cubic-bezier(0.77, 0, 0.18, 1),
                opacity 0.3s,
                width 0.4s cubic-bezier(0.77, 0, 0.18, 1),
                box-shadow 0.3s;
    transform-origin: center;
}

.elite-hamburger__line:first-child {
    width: 28px;
}

.elite-hamburger__line:last-child {
    width: 18px;
    align-self: flex-end;
}

/* Hover animation: lines equalize + spread apart + subtle glow */
.elite-hamburger:hover .elite-hamburger__line:first-child {
    transform: translateY(-2px);
    box-shadow: 0 0 8px rgba(255,255,255,0.4);
}

.elite-hamburger:hover .elite-hamburger__line:last-child {
    width: 28px;
    transform: translateY(2px);
    box-shadow: 0 0 8px rgba(255,255,255,0.4);
}

/* Active state (X) */
.elite-hamburger.active .elite-hamburger__line:first-child {
    transform: translateY(4.5px) rotate(45deg);
    width: 28px;
    box-shadow: none;
}

.elite-hamburger.active .elite-hamburger__line:last-child {
    transform: translateY(-4.5px) rotate(-45deg);
    width: 28px;
    box-shadow: none;
}

/* Active hover: X rotates slightly */
.elite-hamburger.active:hover .elite-hamburger__line:first-child {
    transform: translateY(4.5px) rotate(135deg);
    box-shadow: 0 0 8px rgba(255,255,255,0.3);
}

.elite-hamburger.active:hover .elite-hamburger__line:last-child {
    transform: translateY(-4.5px) rotate(-135deg);
    box-shadow: 0 0 8px rgba(255,255,255,0.3);
}

/* ============ OVERLAY MENU ============ */
.elite-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(10, 20, 38, 0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1),
                visibility 0.5s;
}

.elite-overlay.active {
    opacity: 1;
    visibility: visible;
}

.elite-overlay__content {
    text-align: center;
    width: 100%;
    max-width: 600px;
    padding: 40px 20px;
}

.elite-overlay__nav {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 50px;
}

.elite-overlay__link {
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 32px;
    font-weight: 300;
    letter-spacing: 3px;
    text-transform: uppercase;
    padding: 10px 0;
    transition: color 0.2s, letter-spacing 0.3s;
    opacity: 0;
    transform: translateY(20px);
}

.elite-overlay.active .elite-overlay__link {
    opacity: 1;
    transform: translateY(0);
    transition: color 0.2s, letter-spacing 0.3s,
                opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.elite-overlay__link:hover {
    color: #fff;
    letter-spacing: 6px;
    text-decoration: none;
}

.elite-overlay__bottom {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.5s 0.3s, transform 0.5s 0.3s;
}

.elite-overlay.active .elite-overlay__bottom {
    opacity: 1;
    transform: translateY(0);
}

.elite-overlay__socials {
    display: flex;
    gap: 20px;
}

.elite-overlay__socials a {
    color: rgba(255,255,255,0.5);
    font-size: 18px;
    transition: color 0.2s, transform 0.2s;
    text-decoration: none;
}

.elite-overlay__socials a:hover {
    color: #fff;
    transform: scale(1.2);
}

.elite-overlay__email {
    color: rgba(255,255,255,0.4);
    font-size: 13px;
    letter-spacing: 1px;
    text-decoration: none;
}

.elite-overlay__email:hover {
    color: rgba(255,255,255,0.7);
    text-decoration: none;
}

.elite-overlay__auth {
    display: flex;
    gap: 20px;
}

.elite-overlay__auth a {
    color: rgba(255,255,255,0.5);
    font-size: 13px;
    text-decoration: none;
    transition: color 0.2s;
}

.elite-overlay__auth a:hover {
    color: #fff;
}

/* ============ MOBILE ============ */
@media (max-width: 991px) {
    .elite-header {
        padding: 0 16px;
    }

    .elite-header__inner {
        height: 60px;
    }

    .elite-header__nav,
    .elite-header__hr-wrap {
        display: none !important;
    }

    .elite-header__right {
        flex: 1;
        justify-content: flex-end;
    }

    .elite-logo-anim__img {
        height: 36px;
    }

    .elite-overlay__link {
        font-size: 24px;
        letter-spacing: 2px;
    }
}

@media (min-width: 992px) and (max-width: 1200px) {
    .elite-header__link {
        font-size: 12px;
        letter-spacing: 1.2px;
    }

    .elite-header__nav {
        gap: 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var header = document.getElementById('elite-header');
    var hamburger = document.getElementById('elite-hamburger');
    var overlay = document.getElementById('elite-overlay');

    // Remove admin bar and any parent wrappers
    var adminBar = document.querySelector('.admin-bar-container') || document.querySelector('#admin-bar');
    if (adminBar) {
        var parent = adminBar.parentElement;
        adminBar.remove();
        if (parent && parent !== document.body && parent.children.length === 0) {
            parent.remove();
        }
    }
    document.body.classList.remove('show-admin-bar');
    document.body.style.paddingTop = '';
    document.body.style.marginTop = '';
    // Remove admin-bar stylesheet
    document.querySelectorAll('link[href*="admin-bar"]').forEach(function(el) { el.remove(); });

    // Logo entrance animation
    setTimeout(function () {
        header.classList.add('loaded');
    }, 150);

    // Scroll effect
    function onScroll() {
        if (window.scrollY > 30) {
            header.classList.add('elite-header--scrolled');
        } else {
            header.classList.remove('elite-header--scrolled');
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Hamburger toggle
    hamburger.addEventListener('click', function () {
        var isActive = hamburger.classList.toggle('active');
        overlay.classList.toggle('active', isActive);
        document.body.style.overflow = isActive ? 'hidden' : '';
    });

    // Close overlay on link click
    overlay.querySelectorAll('.elite-overlay__link').forEach(function (link) {
        link.addEventListener('click', function () {
            hamburger.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    });

    // Close on ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            hamburger.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
</script>
