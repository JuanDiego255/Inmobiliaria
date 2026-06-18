<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Forms\FormAbstract;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Http\Requests\CrmLeadRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\CrmLead;

class CrmLeadForm extends FormAbstract
{
    public function buildForm(): void
    {
        $agents = Account::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->pluck('name', 'id')
            ->prepend('-- Sin asignar --', '')
            ->toArray();

        $this
            ->setupModel(new CrmLead())
            ->setValidatorClass(CrmLeadRequest::class)
            ->withCustomFields()
            ->add('name', 'text', [
                'label' => 'Nombre',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nombre del contacto',
                    'data-counter' => 200,
                ],
            ])
            ->add('email', 'email', [
                'label' => 'Email',
                'attr' => [
                    'placeholder' => 'Email',
                    'data-counter' => 120,
                ],
            ])
            ->add('phone', 'text', [
                'label' => 'Teléfono',
                'attr' => [
                    'placeholder' => 'Teléfono',
                    'data-counter' => 25,
                ],
            ])
            ->add('rowOpen1', 'html', [
                'html' => '<div class="row">',
            ])
            ->add('budget_min', 'number', [
                'label' => 'Presupuesto mínimo',
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-6',
                ],
                'attr' => [
                    'placeholder' => '0',
                    'step' => '0.01',
                ],
            ])
            ->add('budget_max', 'number', [
                'label' => 'Presupuesto máximo',
                'wrapper' => [
                    'class' => 'form-group mb-3 col-md-6',
                ],
                'attr' => [
                    'placeholder' => '0',
                    'step' => '0.01',
                ],
            ])
            ->add('rowClose1', 'html', [
                'html' => '</div>',
            ])
            ->add('assigned_agent_id', 'customSelect', [
                'label' => 'Agente asignado',
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => $agents,
            ])
            ->add('expected_close_date', 'datePicker', [
                'label' => 'Fecha estimada de cierre',
            ])
            ->add('notes', 'textarea', [
                'label' => 'Notas',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Notas sobre el lead...',
                    'data-counter' => 5000,
                ],
            ])
            ->add('stage', 'customSelect', [
                'label' => 'Etapa',
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => CrmLeadStageEnum::labels(),
            ])
            ->add('source', 'customSelect', [
                'label' => 'Fuente',
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => CrmLeadSourceEnum::labels(),
            ])
            ->setBreakFieldPoint('stage');
    }
}
