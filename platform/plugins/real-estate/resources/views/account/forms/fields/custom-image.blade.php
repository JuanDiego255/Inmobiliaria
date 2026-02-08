@if ($showLabel && $showField)
    @if ($options['wrapper'] !== false)
        <div {!! $options['wrapperAttrs'] !!}>
    @endif
@endif

@if ($showLabel && $options['label'] !== false && $options['label_show'])
    {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
@endif

@if ($showField)
    <div class="image-box">
        <input
            class="image-data"
            name="{{ $name }}"
            type="hidden"
            value="{{ $options['value'] }}"
        >
        <input
            class="image_input"
            name="{{ $name }}_input"
            type="file"
            style="display: none;"
            accept="image/*"
        >
        <div class="preview-image-wrapper">
            <img
                class="preview_image"
                src="{{ RvMedia::getImageUrl($options['value'], 'thumb', false, RvMedia::getDefaultImage()) }}"
                alt="preview image"
                width="150"
            >
            <a
                class="btn_remove_image"
                title="{{ trans('core/base::forms.remove_image') }}"
            >
                <i class="fa fa-times"></i>
            </a>
        </div>
        <div class="image-box-actions">
            <a
                class="custom-select-image"
                href="#"
            >
                {{ trans('core/base::forms.choose_image') }}
            </a>
        </div>
    </div>
    @include('core/base::forms.partials.help-block')
@endif

@include('core/base::forms.partials.errors')

@if ($showLabel && $showField)
    @if ($options['wrapper'] !== false)
        </div>
    @endif
@endif
