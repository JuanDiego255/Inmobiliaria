<section class="main-homes">
    <div
        class="bgheadproject hidden-xs"
        style="background: url('{{ theme_option('breadcrumb_background') ? RvMedia::url(theme_option('breadcrumb_background')) : Theme::asset()->url('images/banner-du-an.jpg') }}')"
    >
        <div class="description">
            <div class="container-fluid w90">
                <h1 class="text-center">{{ $category->name }}</h1>
                <p class="text-center d-none d-sm-inline-block">{{ $category->description }}</p>
                {!! Theme::partial('breadcrumb') !!}
            </div>
        </div>
    </div>
    <div class="container-fluid w90 padtop30">
        <div class="projecthome">
            @if ($properties->count())
                <h5 class="headifhouse">{{ __('Properties in :name', ['name' => $category->name]) }}</h5>
                <div class="row rowm10">
                    @foreach ($properties as $property)
                        <div
                            class="col-sm-6 col-lg-4 col-xl-3 colm10"
                            style="margin-bottom: 20px;"
                        >
                            {!! Theme::partial('real-estate.properties.item', ['property' => $property]) !!}
                        </div>
                    @endforeach
                </div>
            @else
                <p class="item">{{ __('0 results') }}</p>
            @endif
        </div>
    </div>
</section>
<br>
<div class="col-sm-12">
    <nav
        class="d-flex justify-content-center pt-3"
        aria-label="Page navigation example"
    >
        {!! $properties->withQueryString()->links() !!}
    </nav>
</div>
<br>
<br>
