<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FormAbstract;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Requests\AccountCreateRequest;
use Botble\RealEstate\Models\Account;
use Carbon\Carbon;

class AccountForm extends FormAbstract
{
    protected $template = 'plugins/real-estate::account.admin.form';

    public function buildForm(): void
    {
        Assets::addStylesDirectly('vendor/core/plugins/real-estate/css/account-admin.css')
            ->addScriptsDirectly(['/vendor/core/plugins/real-estate/js/account-admin.js']);

        $this
            ->setupModel(new Account())
            ->setValidatorClass(AccountCreateRequest::class)
            ->withCustomFields()
            ->add('first_name', 'text', [
                'label' => trans('plugins/real-estate::account.first_name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::account.first_name'),
                    'data-counter' => 120,
                ],
            ])
            ->add('last_name', 'text', [
                'label' => trans('plugins/real-estate::account.last_name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::account.last_name'),
                    'data-counter' => 120,
                ],
            ])
            ->add('username', 'text', [
                'label' => trans('plugins/real-estate::account.username'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::account.username_placeholder'),
                    'data-counter' => 120,
                ],
            ])
            ->add('company', 'text', [
                'label' => trans('plugins/real-estate::account.company'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::account.company_placeholder'),
                    'data-counter' => 255,
                ],
            ])
            ->add('description', 'editor', [
                'label' => trans('plugins/real-estate::account.description'),
            ])
            ->add('phone', 'text', [
                'label' => trans('plugins/real-estate::account.phone'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::account.phone_placeholder'),
                    'data-counter' => 20,
                ],
            ])
            ->add('dob', 'datePicker', [
                'label' => trans('plugins/real-estate::account.dob'),
                'attr' => [
                    'data-date-format' => config('core.base.general.date_format.js.date'),
                ],
                'default_value' => BaseHelper::formatDate(Carbon::now()),
            ])
            ->add('email', 'text', [
                'label' => trans('plugins/real-estate::account.form.email'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::account.email_placeholder'),
                    'data-counter' => 60,
                ],
            ]);

        if (is_plugin_active('location')) {
            $this->add('location', 'selectLocation', [
                'wrapper' => [
                    'class' => 'form-group mb-0 col-sm-4',
                ],
                'wrapperClassName' => 'row g-1',
            ]);
        }

        $this
            ->add('is_change_password', 'checkbox', [
                'label' => trans('plugins/real-estate::account.form.change_password'),
                'attr' => [
                    'class' => 'hrv-checkbox',
                ],
                'value' => 1,
            ])
            ->add('password', 'password', [
                'label' => trans('plugins/real-estate::account.form.password'),
                'required' => true,
                'attr' => [
                    'data-counter' => 60,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ($this->getModel()->id ? ' hidden' : null),
                ],
            ])
            ->add('password_confirmation', 'password', [
                'label' => trans('plugins/real-estate::account.form.password_confirmation'),
                'required' => true,
                'attr' => [
                    'data-counter' => 60,
                ],
                'wrapper' => [
                    'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ($this->getModel()->id ? ' hidden' : null),
                ],
            ])
            ->add('avatar_image', 'mediaImage', [
                'value' => $this->getModel()->avatar->url,
            ])
            ->setBreakFieldPoint('avatar_image')

            ->add('is_featured', 'onOff', [
                'label' => trans('core/base::forms.is_featured'),
                'default_value' => false,
            ])
            ->add('is_public_profile', 'onOff', [
                'label' => trans('plugins/real-estate::account.form.is_public_profile'),
                'default_value' => false,
            ]);

        if ($this->getModel()->id && RealEstateHelper::isEnabledCreditsSystem()) {
            $this->addMetaBoxes([
                'credits' => [
                    'title' => null,
                    'content' => view('plugins/real-estate::account.admin.credits', [
                        'account' => $this->model,
                        'transactions' => $this->model->transactions()->orderBy('created_at', 'DESC')->get(),
                    ])->render(),
                    'wrap' => false,
                ],
            ]);
        }
    }
}
