<div class="advanced-search">
    <a href="#" class="advanced-search-toggler">{{ __('Advanced') }} <i class="fas fa-caret-down"></i></a>
    <div class="advanced-search-content property-advanced-search">
        <div class="form-group">
            <div class="row">
                <div class="col-sm-3 px-md-1">
                    {!! Theme::partial('real-estate.filters.by-project') !!}
                </div>
                <div class="col-sm-3 px-md-1">
                    {!! Theme::partial('real-estate.filters.categories', compact('categories')) !!}
                </div>
                {!! Theme::partial('real-estate.filters.price') !!}
            </div>

            <div class="row">
                <div class="col-md-4 col-sm-6 px-md-1">
                    {!! Theme::partial('real-estate.filters.bedroom') !!}
                </div>
                <div class="col-md-4 col-sm-6 px-md-1">
                    {!! Theme::partial('real-estate.filters.bathroom') !!}
                </div>
                <div class="col-md-4 col-sm-6 px-md-1">
                    {!! Theme::partial('real-estate.filters.floor') !!}
                </div>
            </div>
        </div>
    </div>

    @if ($enableProjectsSearch)
        <div class="advanced-search-content project-advanced-search">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        {!! Theme::partial('real-estate.filters.categories', compact('categories')) !!}
                    </div>
                    {!! Theme::partial('real-estate.filters.price') !!}
                </div>
            </div>
        </div>
    @endif
</div>
