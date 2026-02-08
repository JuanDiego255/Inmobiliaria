<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Forms\FormAbstract;
use Botble\RealEstate\Enums\ClientStatusEnum;
use Botble\RealEstate\Http\Requests\ClientRequest;
use Botble\RealEstate\Models\Client;

class ClientForm extends FormAbstract
{
    public function buildForm(): void
    {
        $this
            ->setupModel(new Client())
            ->setValidatorClass(ClientRequest::class)
            ->withCustomFields()
            ->add('name', 'text', [
                'label' => trans('plugins/real-estate::client.form.name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::client.form.name'),
                    'data-counter' => 200,
                ],
            ])
            ->add('email', 'email', [
                'label' => trans('plugins/real-estate::client.form.email'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::client.form.email'),
                    'data-counter' => 120,
                ],
            ])
            ->add('phone', 'text', [
                'label' => trans('plugins/real-estate::client.form.phone'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::client.form.phone'),
                    'data-counter' => 25,
                ],
            ])
            ->add('address', 'text', [
                'label' => trans('plugins/real-estate::client.form.address'),
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::client.form.address'),
                    'data-counter' => 255,
                ],
            ])
            ->add('rowOpen', 'html', [
                'html' => '<div class="row">',
            ])
            ->add('occupation', 'text', [
                'label' => trans('plugins/real-estate::client.form.occupation'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-6',
                ],
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::client.form.occupation'),
                    'data-counter' => 120,
                ],
            ])
            ->add('age', 'number', [
                'label' => trans('plugins/real-estate::client.form.age'),
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-6',
                ],
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::client.form.age'),
                    'min' => 1,
                    'max' => 150,
                ],
            ])
            ->add('rowClose', 'html', [
                'html' => '</div>',
            ])
            ->add('notes', 'textarea', [
                'label' => trans('plugins/real-estate::client.form.notes'),
                'attr' => [
                    'rows' => 4,
                    'placeholder' => trans('plugins/real-estate::client.form.notes_placeholder'),
                    'data-counter' => 5000,
                ],
            ])
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => ClientStatusEnum::labels(),
            ])
            ->setBreakFieldPoint('status');
    }
}
