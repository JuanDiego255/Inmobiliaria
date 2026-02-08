<div
    class="col-md-12"
    id="custom-field-options"
>
    <table class="table table-bordered setting-option">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">{{ trans('plugins/real-estate::custom-fields.option.label') }}</th>
                <th
                    scope="col"
                    colspan="2"
                >{{ trans('plugins/real-estate::custom-fields.option.value') }}</th>
            </tr>
        </thead>
        <tbody class="option-sortable">
            <input
                name="is_global"
                type="hidden"
                value="1"
            >
            @if ($options->count())
                @foreach ($options as $key => $value)
                    <tr
                        class="option-row ui-state-default"
                        data-index="{{ $value->id }}"
                    >
                        <input
                            name="options[{{ $key }}][id]"
                            type="hidden"
                            value="{{ $value->id }}"
                        >
                        <input
                            name="options[{{ $key }}][order]"
                            type="hidden"
                            value="{{ $value->order !== 999 ? $value->order : $key }}"
                        >
                        <td class="text-center">
                            <i class="fa fa-sort"></i>
                        </td>
                        <td>
                            <input
                                class="form-control option-label"
                                name="options[{{ $key }}][label]"
                                type="text"
                                value="{{ $value->label }}"
                                placeholder="{{ trans('plugins/real-estate::custom-fields.option.label') }}"
                            />
                        </td>
                        <td>
                            <input
                                class="form-control option-value"
                                name="options[{{ $key }}][value]"
                                type="text"
                                value="{{ $value->value }}"
                                placeholder="{{ trans('plugins/real-estate::custom-fields.option.value') }}"
                            />
                        </td>
                        <td style="width: 50px">
                            <button
                                class="btn btn-default remove-row"
                                data-index="0"
                                type="button"
                            >
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr
                    class="option-row"
                    data-index="0"
                >
                    <td class="text-center">
                        <i class="fa fa-sort"></i>
                    </td>
                    <td>
                        <input
                            class="form-control option-label"
                            name="options[0][label]"
                            type="text"
                            placeholder="{{ trans('plugins/real-estate::custom-fields.option.label') }}"
                        />
                    </td>
                    <td>
                        <input
                            class="form-control option-value"
                            name="options[0][value]"
                            type="text"
                            placeholder="{{ trans('plugins/real-estate::custom-fields.option.value') }}"
                        />
                    </td>
                    <td style="width: 50px">
                        <button
                            class="btn btn-default remove-row"
                            data-index="0"
                            type="button"
                        >
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <button
        class="btn btn-info mt-3"
        id="add-new-row"
        type="button"
    >{{ trans('plugins/real-estate::custom-fields.option.add_row') }}</button>
</div>
