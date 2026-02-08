<?php

namespace Botble\RealEstate\Forms;

use Botble\Base\Forms\FormAbstract;
use Botble\RealEstate\Enums\BoardStatusEnum;
use Botble\RealEstate\Http\Requests\BoardRequest;
use Botble\RealEstate\Models\Board;
use Botble\RealEstate\Models\Client;

class BoardForm extends FormAbstract
{
    public function buildForm(): void
    {
        $clients = Client::query()
            ->select('name', 'id')
            ->latest()
            ->get()
            ->mapWithKeys(fn (Client $item) => [$item->getKey() => $item->name])
            ->all();

        $this
            ->setupModel(new Board())
            ->setValidatorClass(BoardRequest::class)
            ->withCustomFields()
            ->add('name', 'text', [
                'label' => trans('plugins/real-estate::board.form.name'),
                'required' => true,
                'attr' => [
                    'placeholder' => trans('plugins/real-estate::board.form.name'),
                    'data-counter' => 255,
                ],
            ])
            ->add('description', 'textarea', [
                'label' => trans('plugins/real-estate::board.form.description'),
                'attr' => [
                    'rows' => 3,
                    'placeholder' => trans('plugins/real-estate::board.form.description_placeholder'),
                    'data-counter' => 5000,
                ],
            ])
            ->add('client_id', 'customSelect', [
                'label' => trans('plugins/real-estate::board.form.client'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-search-full',
                ],
                'choices' => ['' => trans('plugins/real-estate::board.form.select_client')] + $clients,
            ])
            ->add('status', 'customSelect', [
                'label' => trans('core/base::tables.status'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control select-full',
                ],
                'choices' => BoardStatusEnum::labels(),
            ])
            ->setBreakFieldPoint('status');

        if ($this->getModel() && $this->getModel()->id) {
            $this->addMetaBoxes([
                'properties' => [
                    'title' => trans('plugins/real-estate::board.board_properties'),
                    'content' => view('plugins/real-estate::boards.board-properties', [
                        'board' => $this->getModel(),
                        'properties' => $this->getModel()->properties,
                    ])->render(),
                    'priority' => 0,
                ],
                'share_links' => [
                    'title' => trans('plugins/real-estate::board.share'),
                    'content' => view('plugins/real-estate::boards.share-links', [
                        'board' => $this->getModel(),
                    ])->render(),
                    'attributes' => [
                        'style' => 'margin-top: 0',
                    ],
                ],
            ]);
        }
    }
}
