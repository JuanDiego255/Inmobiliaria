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

<div class="form-group mb-3">
    <label class="control-label">{{ __('City') }}</label>
    <input
        class="form-control list-tagify"
        name="city"
        data-list="{{ json_encode($cities) }}"
        value="{{ Arr::get($attributes, 'city') }}"
        placeholder="{{ __('Select city from the list') }}"
    >
</div>

<div class="form-group mb-3">
    <label class="control-label">{{ __('State') }}</label>
    <input
        class="form-control list-tagify"
        name="state"
        data-list="{{ json_encode($states) }}"
        value="{{ Arr::get($attributes, 'state') }}"
        placeholder="{{ __('Select state from the list') }}"
    >
</div>
