<div class="form-group mb-3">
    <label class="control-label">{{ __('Title') }}</label>
    <input
        class="form-control"
        name="title"
        value="{{ Arr::get($attributes, 'title') }}"
    >
</div>

<div class="form-group mb-3">
    <label class="control-label">{{ __('Subtitle') }}</label>
    <textarea
        class="form-control"
        name="subtitle"
        rows="3"
    >{{ Arr::get($attributes, 'subtitle') }}</textarea>
</div>

<div class="form-group">
    <label class="control-label">{{ __('Show only featured properties?') }}</label>
    {!! Form::customSelect('featured', ['1' => __('Yes'), '0' => __('No')], Arr::get($attributes, 'featured', 1)) !!}
</div>

<div class="form-group mb-3">
    <label class="control-label">{{ __('Limit') }}</label>
    <input
        class="form-control"
        name="limit"
        value="{{ Arr::get($attributes, 'limit', 8) }}"
    />
</div>
