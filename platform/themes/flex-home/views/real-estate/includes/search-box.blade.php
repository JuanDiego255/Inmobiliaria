@php
    $states = \Botble\Location\Models\State::query()
        ->wherePublished()
        ->orderBy('order')
        ->orderBy('name')
        ->select(['id', 'name'])
        ->get();
@endphp

<div class="search-box-filters-modern">
    <div class="filters-row">
        {{-- Keyword --}}
        <div class="filter-item">
            <label>{{ __('Keyword') }}</label>
            <input type="text" name="k" class="form-control" placeholder="{{ __('Enter keyword...') }}"
                   value="{{ BaseHelper::stringify(request()->input('k')) }}">
        </div>

        {{-- Category --}}
        <div class="filter-item">
            <label>{{ __('Tipos de propiedades') }}</label>
            <select name="category_id" class="form-control">
                <option value="">{{ __('All') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @if(request()->input('category_id') == $category->id) selected @endif>
                        {!! BaseHelper::clean($category->indent_text) !!} {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Type --}}
        <div class="filter-item">
            <label>{{ __('Type') }}</label>
            <select name="type" class="form-control">
                <option value="">{{ __('All') }}</option>
                @foreach(\Botble\RealEstate\Enums\PropertyTypeEnum::labels() as $key => $label)
                    <option value="{{ $key }}" @if(request()->input('type') == $key) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- State --}}
        <div class="filter-item">
            <label>{{ __('State') }}</label>
            <select name="state_id" class="form-control" id="filter-state">
                <option value="">{{ __('All') }}</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}" @if(request()->input('state_id') == $state->id) selected @endif>{{ $state->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- City --}}
        <div class="filter-item">
            <label>{{ __('City') }}</label>
            <select name="city_id" class="form-control" id="filter-city"
                    data-url="{{ route('ajax.cities-by-state') }}">
                <option value="">{{ __('All') }}</option>
            </select>
        </div>

        @if ($type == 'property')
            {{-- Bedrooms --}}
            <div class="filter-item">
                <label>{{ __('Bedrooms') }}</label>
                <select name="bedroom" class="form-control">
                    <option value="">{{ __('All') }}</option>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @if(request()->input('bedroom') == $i) selected @endif>{{ $i }}+</option>
                    @endfor
                </select>
            </div>

            {{-- Bathrooms --}}
            <div class="filter-item">
                <label>{{ __('Bathrooms') }}</label>
                <select name="bathroom" class="form-control">
                    <option value="">{{ __('All') }}</option>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @if(request()->input('bathroom') == $i) selected @endif>{{ $i }}+</option>
                    @endfor
                </select>
            </div>

            {{-- Price range --}}
            <div class="filter-item">
                <label>{{ __('Min price') }}</label>
                <input type="number" name="min_price" class="form-control" placeholder="{{ __('Min') }}"
                       value="{{ BaseHelper::stringify(request()->input('min_price')) }}" min="0">
            </div>
            <div class="filter-item">
                <label>{{ __('Max price') }}</label>
                <input type="number" name="max_price" class="form-control" placeholder="{{ __('Max') }}"
                       value="{{ BaseHelper::stringify(request()->input('max_price')) }}" min="0">
            </div>
        @endif

        {{-- Buttons --}}
        <div class="filter-item filter-item--btn" style="align-self: flex-end;">
            <button type="submit" class="btn-search">
                <i class="far fa-search"></i> {{ __('Search') }}
            </button>
        </div>
        <div class="filter-item filter-item--btn" style="align-self: flex-end;">
            <button type="reset" class="btn-reset">
                <i class="far fa-undo"></i> {{ __('Reset') }}
            </button>
        </div>
    </div>
</div>

<script>
    $(function () {
        var filterState = $('#filter-state');
        var filterCity = $('#filter-city');
        var citiesUrl = filterCity.data('url');

        function loadCities(stateId, selectedCityId) {
            if (!stateId) {
                filterCity.html('<option value="">{{ __("All") }}</option>');
                return;
            }
            filterCity.html('<option value="">{{ __("Loading...") }}</option>');
            $.get(citiesUrl, { state_id: stateId }, function (response) {
                var items = response.data || response;
                var html = '<option value="">{{ __("All") }}</option>';
                if (Array.isArray(items)) {
                    items.forEach(function (city) {
                        var id = city.id || city.value;
                        var name = city.name || city.label || city.text;
                        if (id) {
                            var sel = (selectedCityId && String(id) === String(selectedCityId)) ? ' selected' : '';
                            html += '<option value="' + id + '"' + sel + '>' + name + '</option>';
                        }
                    });
                }
                filterCity.html(html);
            });
        }

        filterState.on('change', function () {
            loadCities($(this).val());
        });

        // Load cities on page load if state is pre-selected
        var initialState = filterState.val();
        if (initialState) {
            loadCities(initialState, '{{ request()->input("city_id") }}');
        }
    });
</script>
