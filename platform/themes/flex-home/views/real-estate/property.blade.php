@php
    Theme::asset()->usePath()->add('property-detail-css', 'css/property-detail.css');
    Theme::asset()->container('footer')->usePath()->add('property-detail-js', 'js/property-detail.js');
    Theme::asset()->container('footer')->usePath()->add('property-js', 'js/property.js');

    if (RealEstateHelper::isEnabledReview()) {
        Theme::asset()->usePath()->add('jquery-bar-rating-css', 'libraries/jquery-bar-rating/css-stars.css');
        Theme::asset()->container('footer')->usePath()->add('jquery-bar-rating-js', 'libraries/jquery-bar-rating/jquery.barrating.min.js');
        Theme::asset()->container('footer')->usePath()->add('review-js', 'js/review.js');
    }

    $images = array_values(array_filter((array) $property->images));
    $imageCount = count($images);
    $address = implode(', ', array_filter([$property->city->name ?? '', $property->state->name ?? '']));
    $account = $property->author;
    $isRent = $property->type == \Botble\RealEstate\Enums\PropertyTypeEnum::RENT;
    $dealLabel = $isRent ? __('Alquiler') : __('Venta');
    $categoryName = $property->category->name ?? __('Propiedad');

    $reviewsAvg = $property->reviews_avg_star ?? 0;
    $reviewsCount = $property->reviews_count ?? 0;
@endphp

<div data-property-id="{{ $property->id }}"></div>

<main class="pd-page" style="background:#fff;">
    <div class="pd-wrap">

        {{-- BREADCRUMB --}}
        <nav class="pd-crumb" aria-label="Migas de pan">
            <a href="{{ route('public.index') }}">{{ __('Inicio') }}</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ RealEstateHelper::getPropertiesListPageUrl() }}">{{ __('Propiedades') }}</a>
            <span class="material-icons">chevron_right</span>
            <span class="pd-crumb__cur">{{ $property->name }}</span>
        </nav>

        {{-- GALLERY MOSAIC --}}
        @if($imageCount > 0)
            <section class="pd-gallery @if($imageCount === 1) pd-gallery--single @endif" id="pdGallery">
                <div class="pd-g-cell pd-g-main" data-i="0">
                    <img src="{{ RvMedia::getImageUrl($images[0], null, false, RvMedia::getDefaultImage()) }}" alt="{{ $property->name }}">
                </div>
                @if($imageCount > 1)
                    <div class="pd-g-side">
                        @foreach(array_slice($images, 1, 4) as $idx => $img)
                            <div class="pd-g-cell" data-i="{{ $idx + 1 }}">
                                <img src="{{ RvMedia::getImageUrl($img, null, false, RvMedia::getDefaultImage()) }}" alt="{{ $property->name }}">
                                @if($loop->last && $imageCount > 5)
                                    <div class="pd-g-overlay">+{{ $imageCount - 5 }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if($imageCount > 1)
                    <button class="pd-g-more" type="button">
                        <span class="material-icons">grid_view</span>
                        {{ __('Ver las :count fotos', ['count' => $imageCount]) }}
                    </button>
                @endif
            </section>
        @endif

        {{-- DETAIL GRID --}}
        <div class="pd-detail">

            {{-- MAIN COLUMN --}}
            <div class="pd-main">

                {{-- TITLE BLOCK --}}
                <header>
                    <span class="pd-title-eyebrow">{{ $categoryName }} &middot; {{ $isRent ? __('En alquiler') : __('En venta') }}</span>
                    <h1 class="pd-prop-title">{{ $property->name }}</h1>
                    <div class="pd-title-meta">
                        @if($address)
                            <span class="pd-mi"><span class="material-icons">place</span>{{ $address }}</span>
                        @endif
                        @if (setting('real_estate_display_views_count_in_detail_page', 0) == 1)
                            <span class="pd-mi"><span class="material-icons">visibility</span>{{ number_format($property->views) }} {{ __('vistas') }}</span>
                        @endif
                        <span class="pd-mi"><span class="material-icons">calendar_today</span>{{ $property->created_at->translatedFormat('d M, Y') }}</span>
                        @if(RealEstateHelper::isEnabledReview() && $reviewsCount > 0)
                            <span class="pd-stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="material-icons">{{ $i <= round($reviewsAvg) ? 'star' : 'star_border' }}</span>
                                @endfor
                                <span class="pd-cnt">({{ $reviewsCount }})</span>
                            </span>
                        @endif
                    </div>
                </header>

                <hr class="pd-rule">

                {!! apply_filters('before_single_content_detail', null, $property) !!}

                {{-- FICHA TECNICA --}}
                <section class="pd-sec" style="margin-top:0">
                    <h2 class="pd-sec-h">{{ __('Ficha técnica') }}</h2>
                    <div class="pd-spec-grid">
                        @if($categoryName)
                            <div class="pd-spec-tile">
                                <span class="pd-spec-ic"><span class="material-icons">category</span></span>
                                <div class="pd-spec-v">{{ $categoryName }}</div>
                                <div class="pd-spec-k">{{ __('Categoría') }}</div>
                            </div>
                        @endif
                        @if($property->number_bedroom)
                            <div class="pd-spec-tile">
                                <span class="pd-spec-ic"><span class="material-icons">king_bed</span></span>
                                <div class="pd-spec-v">{{ $property->number_bedroom }}</div>
                                <div class="pd-spec-k">{{ __('Habitaciones') }}</div>
                            </div>
                        @endif
                        @if($property->number_bathroom)
                            <div class="pd-spec-tile">
                                <span class="pd-spec-ic"><span class="material-icons">bathtub</span></span>
                                <div class="pd-spec-v">{{ $property->number_bathroom }}</div>
                                <div class="pd-spec-k">{{ __('Baños') }}</div>
                            </div>
                        @endif
                        @if($property->square)
                            <div class="pd-spec-tile">
                                <span class="pd-spec-ic"><span class="material-icons">straighten</span></span>
                                <div class="pd-spec-v">{{ number_format($property->square) }} m²</div>
                                <div class="pd-spec-k">{{ __('Área') }}</div>
                            </div>
                        @endif
                        @if($property->number_floor)
                            <div class="pd-spec-tile">
                                <span class="pd-spec-ic"><span class="material-icons">layers</span></span>
                                <div class="pd-spec-v">{{ $property->number_floor }}</div>
                                <div class="pd-spec-k">{{ __('Pisos') }}</div>
                            </div>
                        @endif
                    </div>

                    @if($property->customFields->isNotEmpty())
                        <div class="pd-custom-fields">
                            <table>
                                @foreach($property->customFields as $customField)
                                    <tr>
                                        <td>{!! BaseHelper::clean($customField->name) !!}</td>
                                        <td>{!! BaseHelper::clean($customField->value) !!}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @endif

                    {!! apply_filters('property_details_extra_info', null, $property) !!}
                </section>

                {{-- DESCRIPCION --}}
                @if($property->content)
                    <section class="pd-sec">
                        <h2 class="pd-sec-h">{{ __('Descripción') }}</h2>
                        <div class="pd-desc">
                            {!! BaseHelper::clean($property->content) !!}
                        </div>

                        {{-- Features --}}
                        @if($property->features->count())
                            <div class="pd-amen-block">
                                <div class="pd-amen-head">
                                    <span class="pd-amen-ic"><span class="material-icons">apartment</span></span>
                                    <h4>{{ __('Características') }}</h4>
                                </div>
                                @php $property->features->loadMissing('metadata'); @endphp
                                <ul class="pd-amen-list">
                                    @foreach($property->features as $feature)
                                        <li><span class="material-icons">check_circle</span>{{ $feature->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Facilities --}}
                        @if($property->facilities->isNotEmpty())
                            <div class="pd-amen-block">
                                <div class="pd-amen-head">
                                    <span class="pd-amen-ic"><span class="material-icons">pool</span></span>
                                    <h4>{{ __('Facilidades cercanas') }}</h4>
                                </div>
                                @php $property->facilities->loadMissing('metadata'); @endphp
                                <ul class="pd-amen-list">
                                    @foreach($property->facilities as $facility)
                                        <li>
                                            <span class="material-icons">check_circle</span>
                                            {{ $facility->name }}
                                            @if($facility->pivot->distance)
                                                — {{ $facility->pivot->distance }}
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>
                @endif

                {{-- PROJECT INFO --}}
                @if($property->project_id && ($project = $property->project))
                    <section class="pd-sec">
                        <h2 class="pd-sec-h">{{ __('Proyecto') }}</h2>
                        <div class="pd-card" style="padding:18px;display:flex;gap:18px;align-items:center;">
                            <a href="{{ $project->url }}" style="flex:0 0 auto;width:100px;height:80px;border-radius:var(--pd-radius-sm);overflow:hidden;">
                                <img src="{{ RvMedia::getImageUrl($project->image, null, false, RvMedia::getDefaultImage()) }}" alt="{{ $project->name }}" style="width:100%;height:100%;object-fit:cover;">
                            </a>
                            <div>
                                <a href="{{ $project->url }}" style="font-weight:600;font-size:15px;color:var(--pd-ink-900);text-decoration:none;">{{ $project->name }}</a>
                                <p style="margin:4px 0 0;font-size:13px;color:var(--pd-ink-600);line-height:1.5;">{{ Str::limit($project->description, 120) }}</p>
                            </div>
                        </div>
                    </section>
                @endif

                {{-- MAP --}}
                <section class="pd-sec">
                    <h2 class="pd-sec-h">{{ __('Ubicación') }}</h2>
                    @if($property->latitude && $property->longitude)
                        <div class="pd-map-frame">
                            <div class="pd-map-card">
                                <div class="pd-mc-name"><span class="material-icons">place</span>{{ $property->name }}</div>
                                <div class="pd-mc-addr">{{ $property->location ?: $address }}</div>
                            </div>
                            <iframe
                                title="{{ __('Mapa de ubicación') }}"
                                loading="lazy"
                                src="https://www.openstreetmap.org/export/embed.html?bbox={{ $property->longitude - 0.06 }}%2C{{ $property->latitude - 0.04 }}%2C{{ $property->longitude + 0.06 }}%2C{{ $property->latitude + 0.04 }}&amp;layer=mapnik&amp;marker={{ $property->latitude }}%2C{{ $property->longitude }}"
                            ></iframe>
                        </div>
                        <div class="d-none d-print-block">
                            <a class="text-decoration-none" href="https://maps.google.com/?ll={{ $property->latitude }},{{ $property->longitude }}">
                                {{ $property->location ?: $address }}
                            </a>
                        </div>
                    @elseif($property->location)
                        <div class="pd-map-frame">
                            <iframe
                                title="{{ __('Mapa de ubicación') }}"
                                loading="lazy"
                                src="https://maps.google.com/maps?q={{ urlencode($property->location) }}%20&t=&z=13&ie=UTF8&iwloc=&output=embed"
                            ></iframe>
                        </div>
                    @endif
                </section>

                {{-- VIDEO --}}
                @if($property->video_url)
                    <section class="pd-sec">
                        <h2 class="pd-sec-h">{{ __('Video') }}</h2>
                        <div style="border-radius:var(--pd-radius);overflow:hidden;aspect-ratio:16/9;">
                            @php
                                $videoId = '';
                                if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $property->video_url, $m)) {
                                    $videoId = $m[1];
                                }
                            @endphp
                            @if($videoId)
                                <iframe width="100%" height="100%" src="https://www.youtube.com/embed/{{ $videoId }}" frameborder="0" allowfullscreen style="border:0;"></iframe>
                            @else
                                <iframe width="100%" height="100%" src="{{ $property->video_url }}" frameborder="0" allowfullscreen style="border:0;"></iframe>
                            @endif
                        </div>
                    </section>
                @endif

                <hr class="pd-rule" style="margin:44px 0 30px">

                {!! apply_filters('after_single_content_detail', null, $property) !!}

                {{-- SHARE --}}
                <div class="pd-share-row">
                    <span class="pd-lbl">{{ __('Compartir esta propiedad') }}</span>
                    <div class="pd-share-btns">
                        <a class="pd-share-btn" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg viewBox="0 0 24 24"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5h1.65V3.6c-.3 0-1.3-.1-2.45-.1-2.4 0-4.05 1.47-4.05 4.17v2.32H7.7V13h2.45v8h3.35z"/></svg>
                        </a>
                        <a class="pd-share-btn" href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($property->name) }}" target="_blank" rel="noopener" aria-label="X">
                            <svg viewBox="0 0 24 24"><path d="M17.7 3h3.2l-7 8 8.2 10h-6.4l-5-6.5L4.7 21H1.5l7.5-8.6L1.1 3h6.6l4.5 6 5.5-6zm-1.1 16.2h1.8L7.5 4.7H5.6l11 14.5z"/></svg>
                        </a>
                        <a class="pd-share-btn" href="https://wa.me/?text={{ urlencode($property->name . ' - ' . url()->current()) }}" target="_blank" rel="noopener" aria-label="WhatsApp">
                            <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 00-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1012 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-2.9.8.8-2.8-.2-.3A8.2 8.2 0 1112 20.2zm4.5-6.1c-.25-.13-1.46-.72-1.69-.8-.23-.08-.4-.13-.56.13-.16.25-.64.8-.79.96-.14.17-.29.19-.54.06-.25-.13-1.04-.38-1.98-1.22-.73-.65-1.23-1.46-1.37-1.71-.14-.25-.02-.39.11-.51.11-.11.25-.29.38-.43.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.13.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.46-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.1-.23-.16-.48-.29z"/></svg>
                        </a>
                        <button class="pd-share-btn" type="button" aria-label="{{ __('Copiar enlace') }}" id="pdCopyLink">
                            <svg viewBox="0 0 24 24"><path d="M3.9 12a3.1 3.1 0 013.1-3.1h4V7h-4a5 5 0 100 10h4v-1.9h-4A3.1 3.1 0 013.9 12zM8 13h8v-2H8v2zm9-6h-4v1.9h4a3.1 3.1 0 010 6.2h-4V17h4a5 5 0 000-10z"/></svg>
                        </button>
                    </div>
                </div>

                {!! apply_filters(
                    BASE_FILTER_PUBLIC_COMMENT_AREA,
                    theme_option('facebook_comment_enabled_in_property', 'no') == 'yes' ? Theme::partial('comments') : null,
                ) !!}

                {!! apply_filters('after_property_detail_content', null, $property) !!}

                {{-- REVIEWS --}}
                @if(RealEstateHelper::isEnabledReview())
                    <section class="pd-sec">
                        <h2 class="pd-sec-h">{{ __('Reseñas') }} @if($reviewsCount > 0)<span style="color:var(--pd-ink-400);font-family:Poppins;font-size:20px;font-weight:500">({{ $reviewsCount }})</span>@endif</h2>

                        @if($reviewsCount > 0)
                            {{-- Review summary --}}
                            <div class="pd-rev-summary">
                                <div class="pd-rev-avg">
                                    <div class="pd-num">{{ number_format($reviewsAvg, 1) }}</div>
                                    <div class="pd-stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="material-icons">{{ $i <= round($reviewsAvg) ? 'star' : 'star_border' }}</span>
                                        @endfor
                                    </div>
                                    <div class="pd-cnt2">{{ $reviewsCount }} {{ __('reseñas') }}</div>
                                </div>
                                <div class="pd-rev-bars">
                                    @php
                                        $allReviews = $property->reviews()->where('status', \Botble\RealEstate\Enums\ReviewStatusEnum::APPROVED)->get();
                                        $starDist = [];
                                        for ($s = 5; $s >= 1; $s--) {
                                            $count = $allReviews->where('star', $s)->count();
                                            $pct = $reviewsCount > 0 ? round(($count / $reviewsCount) * 100) : 0;
                                            $starDist[] = ['star' => $s, 'pct' => $pct];
                                        }
                                    @endphp
                                    @foreach($starDist as $dist)
                                        <div class="pd-rev-bar">
                                            <span class="pd-rk">{{ $dist['star'] }}<span class="material-icons">star</span></span>
                                            <span class="pd-bw"><span class="pd-bf" style="width:{{ $dist['pct'] }}%"></span></span>
                                            <span style="width:34px;text-align:right">{{ $dist['pct'] }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="pd-rev-empty">
                                <p>{{ __('Aún no hay reseñas. Sé el primero en opinar.') }}</p>
                            </div>
                        @endif

                        {{-- Reviews list (AJAX loaded by review.js) --}}
                        <div class="reviews-container">
                            <div class="loading-spinner"></div>
                            <div class="pd-rev-list reviews-list @if($reviewsCount) mt-10 @endif" data-url="{{ route('public.ajax.review.index', $property->slug) }}?reviewable_type={{ get_class($property) }}" data-type="{{ get_class($property) }}"></div>
                        </div>

                        {{-- Write review form --}}
                        @php($canReview = auth('account')->check() && auth('account')->user()->canReview($property))
                        <div class="pd-write-rev">
                            <h4>{{ __('Escribe una reseña') }}</h4>
                            <p class="pd-sub">{{ __('Compartí tu experiencia con esta propiedad.') }}</p>
                            <form action="{{ route('public.ajax.review.store', $property->slug) }}" method="post" class="review-form">
                                @csrf
                                <input type="hidden" name="reviewable_type" value="{{ get_class($property) }}">

                                {{-- Hidden select for barrating plugin compatibility --}}
                                <div style="display:none;">
                                    <select name="star" id="select-star">
                                        @foreach(range(1, 5) as $i)
                                            <option value="{{ $i }}" @selected(old('star', 5) == $i)>{{ $i }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="pd-star-input" id="pdStarInput" role="radiogroup" aria-label="{{ __('Calificación') }}">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="material-icons" data-v="{{ $i }}">star</span>
                                    @endfor
                                </div>

                                <textarea class="pd-fld" name="content" placeholder="{{ __('Contanos qué te pareció esta propiedad…') }}" @disabled(!$canReview)>{{ old('content') }}</textarea>

                                <div class="pd-rev-actions">
                                    <button class="pd-btn-primary" type="submit" @disabled(!$canReview)>
                                        {{ __('Enviar opinión') }} <span class="material-icons">send</span>
                                    </button>
                                    @guest('account')
                                        <span class="pd-login-note">
                                            <span class="material-icons">lock</span>
                                            <a href="{{ route('public.account.login') }}">{{ __('Iniciá sesión') }}</a> {{ __('para publicar tu reseña.') }}
                                        </span>
                                    @endguest
                                </div>
                            </form>
                        </div>
                    </section>
                @endif

            </div>

            {{-- ASIDE --}}
            <aside class="pd-aside">

                {{-- PRICE CARD --}}
                <div class="pd-card pd-price-card">
                    <span class="pd-price-badge">{{ $isRent ? __('En alquiler') : __('En venta') }}</span>
                    <div class="pd-price-val">
                        {{ $property->price_format }}
                        @if($isRent && $property->period)
                            <span class="pd-per">/{{ Str::lower($property->period->label()) }}</span>
                        @endif
                    </div>
                    <div class="pd-price-sub">
                        {{ $isRent ? __('Precio de alquiler') : __('Precio de venta') }}
                        · {!! $property->status_html !!}
                    </div>
                    <div class="pd-price-foot">
                        @if($property->square)
                            <span class="pd-pf"><span class="material-icons">straighten</span>{{ number_format($property->square) }} m²</span>
                        @endif
                        @if($property->number_bedroom)
                            <span class="pd-pf"><span class="material-icons">king_bed</span>{{ $property->number_bedroom }} {{ __('hab') }}</span>
                        @endif
                        @if($property->number_bathroom)
                            <span class="pd-pf"><span class="material-icons">bathtub</span>{{ $property->number_bathroom }} {{ __('baños') }}</span>
                        @endif
                    </div>
                </div>

                {{-- AGENT CARD --}}
                @if(!RealEstateHelper::hideAgentInfoInPropertyDetailPage() && $account)
                    <div class="pd-card pd-agent-card">
                        <div class="pd-agent-top">
                            <div class="pd-agent-ava">
                                @if($account->avatar && $account->avatar->url)
                                    <img src="{{ RvMedia::getImageUrl($account->avatar->url, 'thumb') }}" alt="{{ $account->name }}">
                                @else
                                    {{ collect(explode(' ', $account->name))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                @endif
                            </div>
                            <div>
                                <div class="pd-agent-name">
                                    @if($account->url)
                                        <a href="{{ $account->url }}">{{ $account->name }}</a>
                                    @else
                                        {{ $account->name }}
                                    @endif
                                </div>
                                <div class="pd-agent-role">{{ $account->company ?: __('Agente inmobiliario') }}</div>
                            </div>
                        </div>
                        <div class="pd-agent-actions">
                            @php
                                $agentPhone = ($account->phone && !setting('real_estate_hide_agency_phone', 0))
                                    ? $account->phone
                                    : theme_option('hotline');
                            @endphp
                            @if($agentPhone)
                                <a class="pd-btn-primary" href="tel:{{ $agentPhone }}"><span class="material-icons">call</span> {{ __('Llamar') }}</a>
                                <a class="pd-btn-ghost" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $agentPhone) }}" target="_blank" rel="noopener"><span class="material-icons">chat</span> WhatsApp</a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- CONTACT FORM --}}
                <div class="pd-card pd-form-card">
                    <h3>{{ __('Solicitar información') }}</h3>
                    <p class="pd-fc-sub">{{ __('Te contactamos en menos de 24 horas.') }}</p>
                    <form action="{{ route('public.send.consult') }}" method="post" class="pd-fields generic-form" id="contact-form">
                        @csrf
                        <input type="hidden" value="property" name="type">
                        <input type="hidden" value="{{ $property->id }}" name="data_id">
                        <input class="pd-fld" type="text" name="name" placeholder="{{ __('Nombre completo') }} *">
                        @if(!RealEstateHelper::isHiddenFieldAtConsultForm('phone'))
                            <input class="pd-fld" type="tel" name="phone" placeholder="{{ __('Teléfono') }} @if(RealEstateHelper::hasEnabledFieldAtConsultForm('phone')) * @endif">
                        @endif
                        @if(!RealEstateHelper::isHiddenFieldAtConsultForm('email'))
                            <input class="pd-fld" type="email" name="email" placeholder="{{ __('Correo electrónico') }} @if(RealEstateHelper::hasEnabledFieldAtConsultForm('email')) * @endif">
                        @endif
                        <input class="pd-fld pd-fld-locked" type="text" value="{{ $property->name }}" readonly>
                        <textarea class="pd-fld" name="content" placeholder="{{ __('Mensaje') }} *" style="min-height:92px"></textarea>
                        @if(setting('enable_captcha') && is_plugin_active('captcha'))
                            {!! Captcha::display() !!}
                        @endif
                        <button class="pd-btn-primary" type="submit">{{ __('Enviar consulta') }} <span class="material-icons">arrow_forward</span></button>
                    </form>
                    <p class="pd-form-consent">{{ __('Al enviar aceptás ser contactado por la agencia.') }}</p>
                    {!! apply_filters('consult_form_extra_info', null, $property) !!}
                    <div class="alert alert-success text-success text-left" style="display: none;"><span></span></div>
                    <div class="alert alert-danger text-danger text-left" style="display: none;"><span></span></div>
                </div>

            </aside>
        </div>

        {{-- RELATED PROPERTIES --}}
        @php
            $relatedProperties = app(\Botble\RealEstate\Repositories\Interfaces\PropertyInterface::class)->getRelatedProperties($property->id, 8, \Botble\RealEstate\Facades\RealEstateHelper::getPropertyRelationsQuery());
        @endphp
        @if($relatedProperties->isNotEmpty())
            <div class="pd-related">
                <h2 class="pd-sec-h">{{ __('Propiedades relacionadas') }}</h2>
                <div class="row rowm10">
                    @foreach($relatedProperties as $relatedProperty)
                        <div class="col-sm-6 col-lg-4 col-xl-3 colm10">
                            {!! Theme::partial('real-estate.properties.item', ['property' => $relatedProperty]) !!}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</main>

{{-- LIGHTBOX --}}
@if($imageCount > 0)
    <div class="pd-lb" id="pdLightbox" role="dialog" aria-modal="true" aria-label="{{ __('Galería de imágenes') }}">
        <div class="pd-lb-top">
            <span class="pd-lb-count" id="pdLbCount">1 / {{ $imageCount }}</span>
            <button class="pd-lb-close" id="pdLbClose" aria-label="{{ __('Cerrar') }}"><span class="material-icons">close</span></button>
        </div>
        <div class="pd-lb-stage">
            <button class="pd-lb-nav" id="pdLbPrev" aria-label="{{ __('Anterior') }}"><span class="material-icons">chevron_left</span></button>
            <img class="pd-lb-img" id="pdLbImg" src="" alt="">
            <button class="pd-lb-nav" id="pdLbNext" aria-label="{{ __('Siguiente') }}"><span class="material-icons">chevron_right</span></button>
        </div>
        <div class="pd-lb-thumbs" id="pdLbThumbs">
            @foreach($images as $i => $img)
                <img src="{{ RvMedia::getImageUrl($img, 'thumb', false, RvMedia::getDefaultImage()) }}" data-full="{{ RvMedia::getImageUrl($img, null, false, RvMedia::getDefaultImage()) }}" data-i="{{ $i }}" alt="">
            @endforeach
        </div>
    </div>
@endif

<button class="pd-to-top" id="pdToTop" aria-label="{{ __('Volver arriba') }}"><span class="material-icons">arrow_upward</span></button>

<script id="traffic-popup-map-template" type="text/x-custom-template">
    {!! Theme::partial('real-estate.properties.map', ['property' => $property]) !!}
</script>
