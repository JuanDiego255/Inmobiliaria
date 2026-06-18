@php
    $address   = theme_option('address');
    $hotline   = theme_option('hotline');
    $email     = theme_option('email');
    $company   = theme_option('company_name');
    $aboutUs   = theme_option('about-us');

    $socialLinks = json_decode(theme_option('social_links'), true) ?: [];
@endphp

{{-- Override page template wrappers inline so styles work regardless of page.blade.php version --}}
<style>
    .bgheadproject { display: none !important; }
    .padtop50, .container.padtop50 {
        max-width: none !important; width: 100% !important;
        padding: 0 !important; margin: 0 !important;
    }
    .padtop50 > .row { margin: 0 !important; }
    .padtop50 > .row > .col-sm-12 { padding: 0 !important; max-width: none !important; flex: 0 0 100% !important; }
    .padtop50 > .row > .col-sm-12 > .scontent { max-width: none !important; }
    .padtop50 + br { display: none !important; }
</style>

{{-- ── DARK HERO BANNER ─────────────────────────────── --}}
<div class="ct-banner">
    <div class="ct-banner__bg" style="background-image: url('{{ theme_option('breadcrumb_background') ? RvMedia::url(theme_option('breadcrumb_background')) : Theme::asset()->url('images/banner-du-an.jpg') }}');"></div>
    <div class="ct-banner__overlay"></div>
    <div class="ct-banner__inner">
        <span class="ct-eyebrow">{{ __('Estamos para ayudarte') }}</span>
        <h1 class="ct-banner__title">{{ __('Hablemos de tu') }}<br><em>{{ __('próxima propiedad') }}</em></h1>
        <p class="ct-banner__text">{{ __('Escribinos o pasá a vernos. Te respondemos a la brevedad para acompañarte en cada paso de la compra, venta o alquiler.') }}</p>
        <div class="ct-banner__crumb">
            <a href="{{ route('public.index') }}">{{ __('Inicio') }}</a>
            <span>&gt;</span>
            <strong>{{ __('Contacto') }}</strong>
        </div>
    </div>
</div>

{{-- ── CONTACT SECTION: Info + Form ─────────────────── --}}
<section class="ct-sec">
    <div class="ct-wrap">
        <div class="ct-grid">

            {{-- LEFT COLUMN: Contact info --}}
            <div class="ct-info ct-reveal-up">
                <span class="ct-eyebrow">{{ __('Información del contacto') }}</span>
                <h2 class="ct-info__title">{{ __('Encontranos y escribinos') }}</h2>
                @if($aboutUs)
                    <p class="ct-info__lead">{{ $aboutUs }}</p>
                @else
                    <p class="ct-info__lead">{{ __('Estamos en el corazón de Grecia, Alajuela. Coordiná una visita o consultanos por cualquier propiedad de nuestro portafolio.') }}</p>
                @endif

                <div class="ct-rows">
                    @if($address)
                        <div class="ct-row">
                            <span class="ct-row__ico"><span class="material-icons">place</span></span>
                            <div>
                                <div class="ct-row__k">{{ __('Dirección') }}</div>
                                <div class="ct-row__v">{{ $address }}</div>
                            </div>
                        </div>
                    @endif
                    @if($hotline)
                        <div class="ct-row">
                            <span class="ct-row__ico"><span class="material-icons">call</span></span>
                            <div>
                                <div class="ct-row__k">{{ __('Línea directa') }}</div>
                                <div class="ct-row__v"><a href="tel:{{ preg_replace('/\s/', '', $hotline) }}">{{ $hotline }}</a></div>
                            </div>
                        </div>
                    @endif
                    @if($email)
                        <div class="ct-row">
                            <span class="ct-row__ico"><span class="material-icons">mail</span></span>
                            <div>
                                <div class="ct-row__k">{{ __('Correo electrónico') }}</div>
                                <div class="ct-row__v"><a href="mailto:{{ $email }}">{{ $email }}</a></div>
                            </div>
                        </div>
                    @endif
                    <div class="ct-row">
                        <span class="ct-row__ico"><span class="material-icons">schedule</span></span>
                        <div>
                            <div class="ct-row__k">{{ __('Horario de atención') }}</div>
                            <div class="ct-row__v">{{ __('Lun a Vie · 8:00 a 18:00') }}</div>
                        </div>
                    </div>
                </div>

                @if(count($socialLinks))
                    <div class="ct-socials">
                        <span class="ct-socials__label">{{ __('Seguinos') }}</span>
                        @foreach($socialLinks as $socialLink)
                            @if(count($socialLink) == 3 && $socialLink[1]['value'] && $socialLink[2]['value'])
                                <a href="{{ $socialLink[2]['value'] }}" title="{{ $socialLink[0]['value'] }}" target="_blank" class="ct-socials__link">
                                    <i class="{{ $socialLink[1]['value'] }}"></i>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: Contact form --}}
            <div class="ct-form-card ct-reveal-up">
                <span class="ct-eyebrow">{{ __('¿Cómo te podemos ayudar?') }}</span>
                <h2 class="ct-form-card__title">{{ __('Envianos un mensaje') }}</h2>
                <p class="ct-form-card__lead">{{ __('Completá el formulario y un asesor se pondrá en contacto con vos lo antes posible.') }}</p>

                <form class="ct-form generic-form" action="{{ route('public.send.contact') }}" method="post">
                    @csrf
                    {!! apply_filters('pre_contact_form', null) !!}

                    <div class="ct-form__row">
                        <div class="ct-form__group ct-form__group--half">
                            <label class="ct-form__label" for="ct-name">{{ __('Nombre') }} <span>*</span></label>
                            <input class="ct-form__input" id="ct-name" name="name" type="text" placeholder="{{ __('Tu nombre completo') }}" required>
                        </div>
                        <div class="ct-form__group ct-form__group--half">
                            <label class="ct-form__label" for="ct-phone">{{ __('Teléfono') }}</label>
                            <input class="ct-form__input" id="ct-phone" name="phone" type="text" placeholder="+506 0000 0000">
                        </div>
                    </div>

                    <div class="ct-form__group">
                        <label class="ct-form__label" for="ct-email">{{ __('Correo electrónico') }} <span>*</span></label>
                        <input class="ct-form__input" id="ct-email" name="email" type="email" placeholder="{{ __('tucorreo@ejemplo.com') }}" required>
                    </div>

                    <div class="ct-form__group">
                        <label class="ct-form__label" for="ct-content">{{ __('Mensaje') }} <span>*</span></label>
                        <textarea class="ct-form__input ct-form__textarea" id="ct-content" name="content" rows="5" minlength="10" placeholder="{{ __('Contanos en qué te podemos ayudar...') }}" required></textarea>
                    </div>

                    @if (is_plugin_active('captcha'))
                        @if (setting('enable_captcha'))
                            <div class="ct-form__group">
                                {!! Captcha::display() !!}
                            </div>
                        @endif
                        @if (setting('enable_math_captcha_for_contact_form', 0))
                            <div class="ct-form__group">
                                {!! app('math-captcha')->input([
                                    'class' => 'ct-form__input',
                                    'id' => 'math-group',
                                    'placeholder' => app('math-captcha')->label(),
                                ]) !!}
                            </div>
                        @endif
                    @endif

                    {!! apply_filters('after_contact_form', null) !!}

                    <div class="alert alert-success text-success text-left" style="display: none;">
                        <span></span>
                    </div>
                    <div class="alert alert-danger text-danger text-left" style="display: none;">
                        <span></span>
                    </div>

                    <div class="ct-form__footer">
                        <button class="ct-form__submit" type="submit">
                            {{ __('Enviar mensaje') }}
                            <span class="material-icons">send</span>
                        </button>
                        <p class="ct-form__disclaimer">{{ __('Al enviar aceptás ser contactado por nuestro equipo. No compartimos tus datos con terceros.') }}</p>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>
