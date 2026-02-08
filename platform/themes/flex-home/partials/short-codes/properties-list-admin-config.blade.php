<div class="form-group mb-3">
    <label class="control-label">{{ __('Title') }}</label>
    <input
        class="form-control"
        name="title"
        value="{{ Arr::get($attributes, 'title') }}"
    >
</div>

<div class="form-group mb-3">
    <label class="control-label">{{ __('Description') }}</label>
    <textarea
        class="form-control"
        name="description"
        rows="3"
    >{{ Arr::get($attributes, 'description') }}</textarea>
</div>

<div class="form-group mb-3">
    <label class="control-label">{{ __('Number of properties per page') }}</label>
    {!! Form::customSelect(
        'per_page',
        RealEstateHelper::getPropertiesPerPageList(),
        Arr::get($attributes, 'per_page', 12),
    ) !!}
</div>
