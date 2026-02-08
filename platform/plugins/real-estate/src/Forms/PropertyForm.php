<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Forms\FormAbstract;
use Botble\RealEstate\Enums\CustomFieldEnum;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyPeriodEnum;
use Botble\RealEstate\Enums\PropertyStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Forms\Fields\CategoryMultiField;
use Botble\RealEstate\Http\Requests\PropertyRequest;
use Botble\RealEstate\Models\Currency;
use Botble\RealEstate\Models\CustomField;
use Botble\RealEstate\Models\Facility;
use Botble\RealEstate\Models\Feature;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use stdClass;

class PropertyForm extends FormAbstract
{
    public function buildForm(): void
    {
        Assets::addStyles(['datetimepicker'])
            ->addScripts(['input-mask'])
            ->addScriptsDirectly([
                'vendor/core/plugins/real-estate/js/real-estate.js',
                'vendor/core/plugins/real-estate/js/components.js',
            ])
            ->addStylesDirectly('vendor/core/plugins/real-estate/css/real-estate.css');

        Assets::usingVueJS();

        $projects = Project::query()
            ->select('name', 'id')
            ->latest()
            ->get()
            ->mapWithKeys(fn (Project $item) => [$item->getKey() => $item->name])
            ->all();

        $currencies = Currency::query()->pluck('title', 'id')->all();

        $selectedCategories = [];
        if ($this->getModel()) {
            $selectedCategories = $this->getModel()->categories()->pluck('category_id')->all();
        }

        if (! $this->formHelper->hasCustomField('categoryMulti')) {
            $this->formHelper->addCustomField('categoryMulti', CategoryMultiField::class);
        }

        $selectedFeatures = [];
        if ($this->getModel()) {
            $selectedFeatures = $this->getModel()->features()->pluck('id')->all();
        }

        $features = Feature::query()->select('id', 'name')->get();

        $facilities = Facility::query()->select('id', 'name')->get();

        if ($this->getModel()) {
            $selectedFacilities = $this->getModel()->facilities()->select('re_facilities.id', 'distance')->get();
        } else {
            $selectedFacilities = collect();

            $oldSelectedFacilities = old('facilities', []);

            if (count($oldSelectedFacilities)) {
                foreach ($oldSelectedFacilities as $oldSelectedFacility) {
                    if (! isset($oldSelectedFacility['id']) || ! isset($oldSelectedFacility['distance'])) {
                        continue;
                    }

                    $item = new stdClass();
                    $item->id = $oldSelectedFacility['id'];
                    $item->distance = $oldSelectedFacility['distance'];

                    $selectedFacilities->add($item);
                }
            }
        }

        $squareUnit = setting('real_estate_square_unit', 'm²') ? '(' . setting('real_estate_square_unit', 'm²') . ')' : null;

        $this
            ->setupModel(new Property())
            ->setValidatorClass(PropertyRequest::class)
            ->withCustomFields()
            ->add('name', 'text', [
                'label' => trans('plugins/real-estate::property.form.name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.form.name'),
                    'data-counter' => 120,
                ],
            ])
            ->add('type', 'customSelect', [
                'label' => trans('plugins/real-estate::property.form.type'),
                'required' => true,
                'choices' => PropertyTypeEnum::labels(),
            ])
            ->add('is_featured', 'onOff', [
                'label' => trans('core/base::forms.is_featured'),
                'default_value' => false,
            ])
            ->add('description', 'textarea', [
                'label' => trans('core/base::forms.description'),
                'attr' => [
                    'rows' => 4,
                    'placeholder' => trans('core/base::forms.description_placeholder'),
                    'data-counter' => 350,
                ],
            ])
            ->add('content', 'editor', [
                'label' => trans('core/base::forms.content'),
                'attr' => [
                    'rows' => 4,
                    'with-short-code' => true,
                ],
            ])
            ->add('images[]', 'mediaImages', [
                'label' => trans('plugins/real-estate::property.form.images'),
                'values' => $this->getModel()->id ? $this->getModel()->images : [],
            ]);

        if (is_plugin_active('location')) {
            $this->add('location_data', 'selectLocation', [
                'wrapper' => [
                    'class' => 'form-group mb-0 col-sm-4',
                ],
                'wrapperClassName' => 'row g-1',
            ]);
        }

        $this
            ->add('location', 'text', [
                'label' => trans('plugins/real-estate::property.form.location'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.form.location'),
                    'data-counter' => 300,
                ],
            ])
            ->add('rowOpen', 'html', [
                'html' => '<div class="row">',
            ])
            ->add('latitude', 'text', [
                'label' => trans('plugins/real-estate::property.form.latitude'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-6',
                ],
                'attr' => [
                    'placeholder' => 'Ex: 1.462260',
                    'data-counter' => 25,
                ],
                'help_block' => [
                    'tag' => 'a',
                    'text' => trans('plugins/real-estate::property.form.latitude_helper'),
                    'attr' => [
                        'href' => 'https://www.latlong.net/convert-address-to-lat-long.html',
                        'target' => '_blank',
                        'rel' => 'nofollow',
                    ],
                ],
            ])
            ->add('longitude', 'text', [
                'label' => trans('plugins/real-estate::property.form.longitude'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-6',
                ],
                'attr' => [
                    'placeholder' => 'Ex: 103.812530',
                    'data-counter' => 25,
                ],
                'help_block' => [
                    'tag' => 'a',
                    'text' => trans('plugins/real-estate::property.form.longitude_helper'),
                    'attr' => [
                        'href' => 'https://www.latlong.net/convert-address-to-lat-long.html',
                        'target' => '_blank',
                        'rel' => 'nofollow',
                    ],
                ],
            ])
            ->add('rowClose', 'html', [
                'html' => '</div>',
            ])
            ->add('rowOpen1', 'html', [
                'html' => '<div class="row">',
            ])
            ->add('number_bedroom', 'number', [
                'label' => trans('plugins/real-estate::property.form.number_bedroom'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-3',
                ],
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.form.number_bedroom'),
                ],
            ])
            ->add('number_bathroom', 'number', [
                'label' => trans('plugins/real-estate::property.form.number_bathroom'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-3',
                ],
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.form.number_bathroom'),
                ],
            ])
            ->add('number_floor', 'number', [
                'label' => trans('plugins/real-estate::property.form.number_floor'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-3',
                ],
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.form.number_floor'),
                ],
            ])
            ->add('square', 'number', [
                'label' => trans('plugins/real-estate::property.form.square', ['unit' => $squareUnit]),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-3',
                ],
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.form.square', ['unit' => $squareUnit]),
                ],
            ])
            ->add('rowClose1', 'html', [
                'html' => '</div>',
            ])
            ->add('rowOpen2', 'html', [
                'html' => '<div class="row">',
            ])
            ->add('price', 'text', [
                'label' => trans('plugins/real-estate::property.form.price'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-4',
                ],
                'attr' => [
                    'id' => 'price-number',
                    'placeholder' => trans('plugins/real-estate::property.form.price'),
                    'class' => 'form-control input-mask-number',
                    'data-thousands-separator' => RealEstateHelper::getThousandSeparatorForInputMask(),
                    'data-decimal-separator' => RealEstateHelper::getDecimalSeparatorForInputMask(),
                ],
            ])
            ->add('currency_id', 'customSelect', [
                'label' => trans('plugins/real-estate::project.form.currency'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-4',
                ],
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => $currencies,
            ])
            ->add('period', 'customSelect', [
                'label' => trans('plugins/real-estate::property.form.period'),
                'required' => true,
                'wrapper' => [
                    'class' => 'form-group mb-3 period-form-group col-md-4' . ($this->getModel()->type != PropertyTypeEnum::RENT ? ' hidden' : null),
                ],
                'attr' => [
                    'class' => 'form-control select-search-full',
                ],
                'choices' => PropertyPeriodEnum::labels(),
            ])
            ->add('rowClose2', 'html', [
                'html' => '</div>',
            ])
            ->add('never_expired', 'onOff', [
                'label' => trans('plugins/real-estate::property.never_expired'),
                'default_value' => true,
            ])
            ->add('auto_renew', 'onOff', [
                'label' => trans('plugins/real-estate::property.renew_notice', ['days' => RealEstateHelper::propertyExpiredDays()]),
                'default_value' => false,
                'wrapper' => [
                    'class' => 'form-group mb-3 auto-renew-form-group' . (! $this->getModel()->id || $this->getModel()->never_expired ? ' hidden' : null),
                ],
            ])
            ->addMetaBoxes([
                'features' => [
                    'title' => trans('plugins/real-estate::property.form.features'),
                    'content' => view(
                        'plugins/real-estate::partials.form-features',
                        compact('selectedFeatures', 'features')
                    )->render(),
                    'priority' => 1,
                ],
                'facilities' => [
                    'title' => trans('plugins/real-estate::property.distance_key'),
                    'content' => view(
                        'plugins/real-estate::partials.form-facilities',
                        compact('facilities', 'selectedFacilities')
                    ),
                    'priority' => 0,
                ],
            ])
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-search-full',
                ],
                'choices' => PropertyStatusEnum::labels(),
                'selected' => (string)$this->model->status ?: PropertyStatusEnum::SELLING,
            ])
            ->add('moderation_status', 'customSelect', [
                'label' => trans('plugins/real-estate::property.moderation_status'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => ModerationStatusEnum::labels(),
                'selected' => (string)$this->model->moderation_status ?: ModerationStatusEnum::APPROVED,
            ])
            ->add('categories[]', 'categoryMulti', [
                'label' => trans('plugins/real-estate::property.form.categories'),
                'choices' => get_property_categories_with_children(),
                'value' => old('categories', $selectedCategories),
            ])
            ->add('unique_id', 'text', [
                'label' => trans('plugins/real-estate::property.unique_id'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::property.unique_id'),
                    'class' => 'form-control',
                ],
            ])
            ->add('project_id', 'customSelect', [
                'label' => trans('plugins/real-estate::property.form.project'),
                'attr' => [
                    'class' => 'form-control select-search-full',
                ],
                'choices' => [0 => trans('plugins/real-estate::property.select_project')] + $projects,
            ])
            ->setBreakFieldPoint('status')
            ->add('author_id', 'autocomplete', [
                'label' => trans('plugins/real-estate::property.account'),
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'id' => 'author_id',
                    'data-url' => route('account.list'),
                ],
                'choices' => $this->getModel()->author_id ?
                    [
                        $this->model->author->id => $this->model->author->name,
                    ]
                    :
                    ['' => trans('plugins/real-estate::property.select_account')],
            ])
            ->setActionButtons(view('plugins/real-estate::forms.form-property-actions', ['property' => $this->model])->render());

        if (RealEstateHelper::isEnabledCustomFields()) {
            Assets::addScriptsDirectly('vendor/core/plugins/real-estate/js/custom-fields.js');

            $customFields = CustomField::query()->select(['name', 'id', 'type'])->get();

            $this->addMetaBoxes([
                'custom_fields_box' => [
                    'title' => trans('plugins/real-estate::custom-fields.name'),
                    'content' => view('plugins/real-estate::custom-fields.custom-fields', [
                        'options' => CustomFieldEnum::labels(),
                        'customFields' => $customFields,
                        'model' => $this->model,
                        'ajax' => is_in_admin(true) ? route('real-estate.custom-fields.get-info') : route('public.account.custom-fields.get-info'),
                    ]),
                    'priority' => 0,
                ],
            ]);
        }
    }
}
