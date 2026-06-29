<style>
/* ── Design Tokens ── */
:root {
    --vt-accent: #e91e63;
    --vt-accent-ink: #c2185b;
    --vt-accent-tint: rgba(233,30,99,.12);
    --vt-page: #ffffff;
    --vt-sec-bg: #f6f5f2;
    --vt-ink-900: #16181d;
    --vt-ink-600: #5b5f66;
    --vt-ink-400: #9aa0a8;
    --vt-line: #e7e6e2;
    --vt-soft: #efeeea;
    --vt-ease: cubic-bezier(.2,.7,.2,1);
}

/* ══ DARK HERO BANNER ══ */
.vt-banner {
    background: #0e1014;
    padding: 130px 0 52px;
    text-align: left;
}
.vt-banner__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 clamp(20px, 5vw, 64px);
}
.vt-banner__eyebrow {
    font-size: 11px; font-weight: 600;
    letter-spacing: .2em; text-transform: uppercase;
    color: var(--vt-accent);
    display: inline-flex; align-items: center; gap: 10px;
}
.vt-banner__eyebrow::before {
    content: ""; width: 24px; height: 1px;
    background: var(--vt-accent); display: inline-block;
}
.vt-banner__title {
    font-family: 'Playfair Display', Georgia, serif;
    font-weight: 500;
    font-size: clamp(28px, 3.8vw, 50px);
    line-height: 1.1; letter-spacing: -.01em;
    margin: 14px 0 0; color: #f3f4f6; max-width: 24ch;
}
.vt-banner__title em {
    font-style: italic; color: var(--vt-accent);
}
.vt-banner__text {
    margin: 14px 0 0; max-width: 52ch;
    font-size: clamp(13px, 1.1vw, 15px);
    line-height: 1.65; color: #6b7280; font-weight: 300;
}
.vt-banner__scroll {
    display: inline-flex; align-items: center; gap: 14px;
    margin-top: 32px;
    font-size: 11px; font-weight: 600;
    letter-spacing: .16em; text-transform: uppercase; color: #6b7280;
}
.vt-banner__dot {
    width: 42px; height: 42px; border-radius: 999px;
    border: 1px solid rgba(255,255,255,.15);
    display: grid; place-items: center;
    color: var(--vt-accent);
    animation: vt-bounce 2.4s ease-in-out infinite;
}
.vt-banner__dot .material-icons { font-size: 18px; }
@keyframes vt-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(6px); }
}
@media (max-width: 600px) {
    .vt-banner { padding: 110px 0 40px; }
    .vt-banner__title { max-width: none; }
}

/* ══ MAIN SECTION ══ */
.vt-sec {
    padding: clamp(48px, 8vh, 96px) 0 clamp(56px, 9vh, 110px);
    background: var(--vt-sec-bg);
}
.vt-wrap {
    max-width: 1280px; margin: 0 auto;
    padding: 0 clamp(20px, 5vw, 64px);
}
.vt-sec-head { margin-bottom: clamp(18px, 3vh, 28px); }
.vt-eyebrow {
    font-size: 12px; font-weight: 600;
    letter-spacing: .22em; text-transform: uppercase;
    color: var(--vt-accent);
    display: inline-flex; align-items: center; gap: 10px;
}
.vt-eyebrow::before {
    content: ""; width: 28px; height: 1px;
    background: var(--vt-accent); display: inline-block;
}
.vt-sec-head h2 {
    font-family: 'Playfair Display', Georgia, serif;
    font-weight: 500;
    font-size: clamp(28px, 4vw, 48px);
    line-height: 1.06; margin: 14px 0 0;
    letter-spacing: -.01em; text-wrap: balance;
    color: var(--vt-ink-900);
}

/* ── Toolbar ── */
.vt-toolbar {
    display: flex; align-items: center;
    justify-content: space-between;
    flex-wrap: wrap; gap: 18px;
    margin-bottom: clamp(22px, 3.5vh, 38px);
}
.vt-count-pill {
    display: inline-flex; align-items: center; gap: 9px;
    font-size: 13px; font-weight: 500; color: var(--vt-ink-600);
}
.vt-count-pill b { color: var(--vt-ink-900); font-weight: 600; }
.vt-count-pill .material-icons { font-size: 19px; color: var(--vt-accent); }
.vt-search { position: relative; flex: 0 1 380px; min-width: 220px; }
.vt-search .material-icons {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    font-size: 19px; color: var(--vt-ink-400); pointer-events: none;
}
.vt-search input {
    width: 100%; font-family: inherit; font-size: 14px;
    background: var(--vt-page); color: var(--vt-ink-900);
    border: 1px solid var(--vt-line); border-radius: 999px;
    padding: 12px 18px 12px 42px; outline: none;
    transition: border-color .2s, box-shadow .2s;
}
.vt-search input::placeholder { color: var(--vt-ink-400); }
.vt-search input:focus {
    border-color: var(--vt-accent);
    box-shadow: 0 0 0 4px var(--vt-accent-tint);
}
.vt-search button {
    position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
    background: var(--vt-accent); border: none; color: #fff;
    border-radius: 999px; width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s;
}
.vt-search button:hover { background: var(--vt-accent-ink); }
.vt-search button .material-icons { font-size: 18px; }

/* ── Tour grid ── */
.vt-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: clamp(18px, 2vw, 26px);
}
@media (max-width: 1100px) { .vt-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .vt-grid { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; } }

/* ── Tour card ── */
.vt-card {
    display: flex; flex-direction: column;
    background: var(--vt-page); border: 1px solid var(--vt-line);
    border-radius: 18px; overflow: hidden;
    text-decoration: none; color: inherit;
    box-shadow: 0 1px 3px rgba(0,0,0,.05), 0 14px 36px rgba(0,0,0,.06);
    transition: transform .4s var(--vt-ease), box-shadow .4s ease, border-color .4s ease;
}
.vt-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 26px 56px rgba(0,0,0,.16);
    border-color: color-mix(in srgb, var(--vt-accent) 40%, var(--vt-line));
}

/* Card cover */
.vt-card-cover {
    position: relative; height: 72px; overflow: hidden;
    background:
        radial-gradient(ellipse 140% 160% at 20% -40%, color-mix(in srgb, var(--vt-accent) 60%, transparent) 0%, transparent 70%),
        radial-gradient(ellipse 100% 180% at 90% 120%, color-mix(in srgb, var(--vt-accent) 40%, transparent) 0%, transparent 60%),
        var(--vt-accent);
}
.vt-card-cover::after {
    content: "";
    position: absolute; inset: 0; pointer-events: none;
    background:
        radial-gradient(circle 60px at 70% 30%, rgba(255,255,255,.18), transparent),
        radial-gradient(circle 40px at 30% 70%, rgba(255,255,255,.12), transparent);
}
.vt-card-cover .material-icons {
    position: absolute; right: -8px; bottom: -14px;
    font-size: 80px; color: #fff; opacity: .14;
    transform: rotate(-8deg);
    transition: transform .5s var(--vt-ease);
}
.vt-card:hover .vt-card-cover .material-icons {
    transform: scale(1.08) translateY(-4px) rotate(-8deg);
}

/* Card image */
.vt-card-media {
    position: relative; aspect-ratio: 16/10; overflow: hidden;
    margin: -36px 16px 0; border-radius: 14px;
    z-index: 2;
    box-shadow: 0 8px 28px rgba(0,0,0,.15);
}
.vt-card-media img {
    width: 100%; height: 100%; object-fit: cover; display: block;
    transition: transform .5s var(--vt-ease);
}
.vt-card:hover .vt-card-media img { transform: scale(1.06); }
.vt-badge {
    position: absolute; top: 10px; left: 10px;
    background: var(--vt-accent);
    box-shadow: 0 0 14px rgba(233,30,99,.4);
    color: #fff; font-size: 10px; font-weight: 600;
    padding: 5px 12px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: .08em;
    display: inline-flex; align-items: center; gap: 4px;
}
.vt-badge .material-icons { font-size: 13px; }

/* Card body */
.vt-card-body {
    padding: 16px 20px 10px; flex: 1;
    display: flex; flex-direction: column;
}
.vt-card-name {
    font-family: 'Playfair Display', Georgia, serif;
    font-weight: 500; font-size: 18px; line-height: 1.2;
    margin: 0 0 8px; color: var(--vt-ink-900);
}
.vt-card-location {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: var(--vt-ink-600); margin-bottom: 10px;
}
.vt-card-location .material-icons { font-size: 16px; color: var(--vt-accent); }
.vt-card-meta {
    display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 4px;
}
.vt-card-meta span {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 12px; color: var(--vt-ink-400);
}
.vt-card-meta .material-icons { font-size: 15px; }

/* Card footer */
.vt-card-foot {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    padding: 14px 20px; border-top: 1px solid var(--vt-line);
    background: var(--vt-soft); margin-top: auto;
}
.vt-card-scenes {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 500; color: var(--vt-ink-600);
}
.vt-card-scenes .material-icons { font-size: 18px; color: var(--vt-accent); }
.vt-card-scenes b { color: var(--vt-ink-900); font-weight: 600; }
.vt-card-cta {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--vt-ink-900); color: var(--vt-page);
    border-radius: 999px; padding: 9px 16px;
    font-size: 12.5px; font-weight: 600;
    text-decoration: none; border: 0; cursor: pointer;
    transition: background .2s, gap .3s var(--vt-ease), transform .2s;
}
.vt-card-cta .material-icons { font-size: 15px; }
.vt-card-cta:hover {
    background: var(--vt-accent); color: #fff;
    gap: 9px; text-decoration: none;
}

/* ── Empty ── */
.vt-empty {
    text-align: center; color: var(--vt-ink-600);
    padding: 64px 0; font-weight: 300; font-size: 15px;
}
.vt-empty .material-icons {
    font-size: 52px; color: var(--vt-ink-400);
    display: block; margin: 0 auto 14px;
}
.vt-empty h3 {
    font-family: 'Playfair Display', Georgia, serif;
    font-weight: 500; font-size: 22px;
    color: var(--vt-ink-900); margin: 0 0 6px;
}
.vt-empty p { margin: 0; max-width: 40ch; display: inline-block; }

/* ── Pagination ── */
.vt-pagination {
    margin-top: clamp(32px, 5vh, 56px);
    display: flex; justify-content: center;
}
.vt-pagination nav { display: flex; align-items: center; gap: 4px; }
.vt-pg-link {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 40px; height: 40px; padding: 0 12px;
    border-radius: 10px; border: 1px solid var(--vt-line);
    background: var(--vt-page); color: var(--vt-ink-600);
    font-size: 14px; font-weight: 500; text-decoration: none;
    transition: all .2s;
}
.vt-pg-link:hover {
    border-color: var(--vt-accent); color: var(--vt-accent);
    background: var(--vt-accent-tint); text-decoration: none;
}
.vt-pg-active {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 40px; height: 40px; padding: 0 12px;
    border-radius: 10px;
    background: var(--vt-accent); color: #fff;
    border: 1px solid var(--vt-accent);
    font-size: 14px; font-weight: 600;
}

/* ── Reveal animations ── */
.vt-reveal-up { will-change: transform, opacity; }
@media (prefers-reduced-motion: no-preference) {
    .vt-reveal-up {
        opacity: 0; transform: translateY(28px);
        transition: opacity .7s var(--vt-ease), transform .7s var(--vt-ease);
    }
    .vt-reveal-up.inview { opacity: 1; transform: none; }

    .vt-grid .vt-card {
        opacity: 0; transform: translateY(36px) scale(.985);
        transition: opacity .6s var(--vt-ease), transform .6s var(--vt-ease);
    }
    .vt-grid .vt-card.inview { opacity: 1; transform: none; }
    .vt-grid .vt-card:nth-child(3n+1) { transition-delay: .04s; }
    .vt-grid .vt-card:nth-child(3n+2) { transition-delay: .11s; }
    .vt-grid .vt-card:nth-child(3n+3) { transition-delay: .18s; }
}
</style>

{{-- Dark hero banner --}}
<div class="vt-banner">
    <div class="vt-banner__inner">
        <span class="vt-banner__eyebrow">{{ __('Experiencia inmersiva') }}</span>
        <h1 class="vt-banner__title">{{ __('Explorá nuestros') }} <em>{{ __('Tours 360°') }}</em></h1>
        <p class="vt-banner__text">{{ __('Recorridos virtuales interactivos para que conozcas cada propiedad desde cualquier lugar, como si estuvieras ahí.') }}</p>
        <div class="vt-banner__scroll">
            <span class="vt-banner__dot"><span class="material-icons">arrow_downward</span></span>
            {{ __('Explorar tours') }}
        </div>
    </div>
</div>

<section class="vt-sec" id="vtToursList">
    <div class="vt-wrap">
        <div class="vt-sec-head vt-reveal-up">
            <span class="vt-eyebrow">{{ __('Tours virtuales') }}</span>
            <h2>{{ __('Recorridos disponibles') }}</h2>
        </div>

        <div class="vt-toolbar vt-reveal-up">
            <span class="vt-count-pill">
                <span class="material-icons">panorama_photosphere</span>
                <b>{{ $meta['total'] ?? count($tours) }}</b>&nbsp;{{ __('tours disponibles') }}
            </span>
            <form class="vt-search" method="GET" action="{{ route('public.virtual-tours') }}">
                <span class="material-icons">search</span>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="{{ __('Buscar por nombre o ubicación…') }}">
                <button type="submit"><span class="material-icons">search</span></button>
            </form>
        </div>

        @if(count($tours))
            <div class="vt-grid">
                @foreach($tours as $tour)
                    <div class="vt-card">
                        <div class="vt-card-cover">
                            <span class="material-icons">panorama_photosphere</span>
                        </div>
                        <div class="vt-card-media">
                            <img src="{{ $tour['main_image'] ?? asset('vendor/core/plugins/real-estate/images/no-image.png') }}"
                                 alt="{{ $tour['name'] ?? 'Tour Virtual' }}"
                                 loading="lazy">
                            <span class="vt-badge"><span class="material-icons">360</span> Tour 360</span>
                        </div>
                        <div class="vt-card-body">
                            <h3 class="vt-card-name">{{ $tour['name'] ?? '' }}</h3>
                            @if(!empty($tour['location']))
                                <div class="vt-card-location">
                                    <span class="material-icons">place</span>
                                    {{ $tour['location'] }}
                                </div>
                            @endif
                            <div class="vt-card-meta">
                                @if(!empty($tour['type_name']))
                                    <span><span class="material-icons">home</span> {{ $tour['type_name'] }}</span>
                                @endif
                                @if(!empty($tour['formatted_price']))
                                    <span><span class="material-icons">sell</span> {{ $tour['formatted_price'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="vt-card-foot">
                            <span class="vt-card-scenes">
                                <span class="material-icons">panorama_photosphere</span>
                                <b>{{ $tour['scenes_count'] ?? 0 }}</b>&nbsp;{{ ($tour['scenes_count'] ?? 0) == 1 ? __('escena') : __('escenas') }}
                            </span>
                            <a href="{{ $tour['tour_url'] ?? '#' }}" target="_blank" rel="noopener" class="vt-card-cta">
                                {{ __('Ver tour') }} <span class="material-icons">north_east</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!empty($meta['last_page']) && $meta['last_page'] > 1)
                <div class="vt-pagination">
                    <nav>
                        @for($i = 1; $i <= $meta['last_page']; $i++)
                            @if($i == ($meta['current_page'] ?? 1))
                                <span class="vt-pg-active">{{ $i }}</span>
                            @else
                                <a class="vt-pg-link" href="{{ route('public.virtual-tours', array_merge(request()->query(), ['page' => $i])) }}">{{ $i }}</a>
                            @endif
                        @endfor
                    </nav>
                </div>
            @endif
        @else
            <div class="vt-empty">
                <span class="material-icons">panorama_photosphere</span>
                <h3>{{ !empty($q) ? __('No se encontraron tours') : __('Tours próximamente') }}</h3>
                <p>{{ !empty($q) ? __('Intentá con otros términos de búsqueda.') : __('Pronto tendremos recorridos virtuales disponibles.') }}</p>
            </div>
        @endif
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) { e.target.classList.add('inview'); observer.unobserve(e.target); }
        });
    }, { threshold: 0.08 });
    document.querySelectorAll('.vt-reveal-up, .vt-grid .vt-card').forEach(function(el) { observer.observe(el); });
});
</script>
