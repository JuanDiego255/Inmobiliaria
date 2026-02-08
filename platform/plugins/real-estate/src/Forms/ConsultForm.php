<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Forms\FormAbstract;
use Botble\RealEstate\Enums\ConsultStatusEnum;
use Botble\RealEstate\Http\Requests\ConsultRequest;
use Botble\RealEstate\Models\Consult;

class ConsultForm extends FormAbstract
{
    public function buildForm(): void
    {
        $this
            ->setupModel(new Consult())
            ->setValidatorClass(ConsultRequest::class)
            ->withCustomFields()
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => ConsultStatusEnum::labels(),
            ])
            ->addMetaBoxes([
                'information' => [
                    'title' => trans('plugins/real-estate::consult.consult_information'),
                    'content' => view('plugins/real-estate::info', ['consult' => $this->getModel()])->render(),
                    'attributes' => [
                        'style' => 'margin-top: 0',
                    ],
                ],
            ])
            ->setBreakFieldPoint('status');
    }
}
