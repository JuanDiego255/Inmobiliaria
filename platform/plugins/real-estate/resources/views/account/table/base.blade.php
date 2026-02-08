@extends('plugins/real-estate::account.layouts.skeleton')
@section('content')
    <div
        class="container page-content"
        style="background: none"
    >
        <div class="table-wrapper">
            <div class="portlet light bordered portlet-no-padding">
                <div class="portlet-title">
                    <div class="caption">
                        <div class="wrapper-action">
                            &nbsp;
                        </div>
                    </div>
                </div>
                <div class="portlet-body">
                    <div
                        class="table-responsive"
                        style="overflow-x: inherit"
                    >
                    @section('main-table')
                        {!! $dataTable->table(compact('id', 'class'), false) !!}
                    @show
                </div>
            </div>
        </div>
    </div>
</div>
@include('core/table::modal')
@include('core/table::partials.modal-item', [
    'type' => 'info',
    'name' => 'modal-confirm-renew',
    'title' => __('Renew confirmation'),
    'content' =>
        (RealEstateHelper::isEnabledCreditsSystem()
            ? __('Are you sure you want to renew this property, it will takes 1 credit from your credits')
            : __('Are you sure you want to renew this property')) . '?',
    'action_name' => __('Yes'),
    'action_button_attributes' => [
        'class' => 'button-confirm-renew',
    ],
])
@endsection
@push('scripts')
{!! $dataTable->scripts() !!}
@endpush
