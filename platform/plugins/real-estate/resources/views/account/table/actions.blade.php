<div class="table-actions">
    @if (!empty($edit))
        <a
            class="btn btn-icon btn-sm btn-primary"
            data-original-title="{{ trans('core/base::tables.edit') }}"
            href="{{ route($edit, $item->id) }}"
        ><i class="fa fa-edit"></i></a>
    @endif

    @if (!empty($delete))
        <a
            class="btn btn-icon btn-sm btn-danger deleteDialog"
            data-section="{{ route($delete, $item->id) }}"
            data-original-title="{{ trans('core/base::tables.delete_entry') }}"
            href="#"
            role="button"
        >
            <i class="fa fa-trash"></i>
        </a>
    @endif

    @if (!empty($extra))
        {!! $extra !!}
    @endif
</div>
