@php
    /** @var \Botble\RealEstate\Tables\Actions\AddToBoardAction $action */
@endphp

<a
    data-bs-toggle="tooltip"
    data-bs-original-title="{{ $action->getLabel() }}"
    href="javascript:void(0);"
    class="btn btn-sm btn-icon btn-add-to-board-table"
    data-property-id="{{ $action->getModel()->getKey() }}"
>
    <i class="{{ $action->getIcon() }}"></i>
    <span class="sr-only">{{ $action->getLabel() }}</span>
</a>
