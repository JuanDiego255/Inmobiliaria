@extends(BaseHelper::getAdminMasterLayoutTemplate())
@section('content')
    <div class="property-view-tabs mb-3">
        <ul class="nav nav-tabs" id="propertyViewTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link active"
                    id="table-view-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#table-view"
                    type="button"
                    role="tab"
                >
                    <i class="fas fa-table me-1"></i> {{ trans('plugins/real-estate::property.view_table') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link"
                    id="grid-view-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#grid-view"
                    type="button"
                    role="tab"
                >
                    <i class="fas fa-th me-1"></i> {{ trans('plugins/real-estate::property.view_grid') }}
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="propertyViewTabsContent">
        <div class="tab-pane fade show active" id="table-view" role="tabpanel">
            @include('core/table::base-table')
        </div>

        <div class="tab-pane fade" id="grid-view" role="tabpanel">
            @include('plugins/real-estate::properties.grid-view')
        </div>
    </div>

    @include('plugins/real-estate::boards.add-to-board-table-modal')
@stop

@push('header')
    <link rel="stylesheet" href="{{ asset('vendor/core/plugins/real-estate/css/property-grid.css') }}">
@endpush

@push('footer')
    <script src="{{ asset('vendor/core/plugins/real-estate/js/property-grid.js') }}"></script>
    <script src="{{ asset('vendor/core/plugins/real-estate/js/add-to-board-table.js') }}"></script>
@endpush
