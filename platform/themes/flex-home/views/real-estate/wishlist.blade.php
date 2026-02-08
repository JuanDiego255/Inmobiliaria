<section class="main-homes wishlist-page">
    <div
        class="bgheadproject hidden-xs"
        style="background: url('{{ theme_option('breadcrumb_background') ? RvMedia::url(theme_option('breadcrumb_background')) : Theme::asset()->url('images/banner-du-an.jpg') }}')"
    >
        <div class="description">
            <div class="container-fluid w90">
                <h1 class="text-center">{{ SeoHelper::getTitle() }}</h1>
                {!! Theme::partial('breadcrumb') !!}
            </div>
        </div>
    </div>
    <div class="container-fluid w90 padtop30">
        <div class="projecthome">
            <div class="row rowm10">
                @forelse ($properties as $property)
                    <div class="col-sm-6 col-lg-4 col-xl-3 colm10">
                        {!! Theme::partial('real-estate.properties.item', ['property' => $property]) !!}
                    </div>
                @empty
                    <div
                        class="alert alert-warning w-100"
                        role="alert"
                    >
                        {{ __('0 results') }}
                    </div>
                @endforelse
            </div>
        </div>
</section>
<br>

@if ($properties->count())
    <div class="col-sm-12">
        <nav
            class="d-flex justify-content-center pt-3"
            aria-label="Page navigation example"
        >
            {!! $properties->withQueryString()->links() !!}
        </nav>
    </div>
@endif
<br>
<br>
