<div class="container-fluid w90">
    <div class="homehouse padtop30">
        <div class="row">
            <div class="col-12">
                <h2>{!! BaseHelper::clean($title) !!}</h2>
                @if ($description)
                    <p>{!! BaseHelper::clean($description) !!}</p>
                @endif
                @if ($subtitle)
                    <p>{!! BaseHelper::clean($subtitle) !!}</p>
                @endif
            </div>
        </div>
        <div class="projecthome mb-2">
            <div class="row rowm10">
                @foreach ($properties as $property)
                    <div class="col-sm-6 col-lg-4 col-xl-3 colm10">
                        {!! Theme::partial('real-estate.properties.item', ['property' => $property]) !!}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
