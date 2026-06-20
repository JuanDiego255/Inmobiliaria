<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Http\Requests\CrmLeadRequest;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Consult;
use Botble\RealEstate\Models\CrmLead;
use Botble\RealEstate\Tables\CrmLeadTable;
use Exception;
use Illuminate\Http\Request;

class CrmLeadController extends BaseController
{
    public function index(CrmLeadTable $table)
    {
        PageTitle::setTitle('CRM - Leads');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
        ]);
        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/crm-modals.js',
        ]);

        return $table->renderTable();
    }

    public function pipeline()
    {
        PageTitle::setTitle('CRM - Pipeline');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
        ]);
        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/crm-modals.js',
            'vendor/core/plugins/real-estate/js/crm-tasks.js',
            'vendor/core/plugins/real-estate/js/crm-pipeline.js',
        ]);

        $agents = Account::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('plugins/real-estate::crm.pipeline', compact('agents'));
    }

    public function getPipelineData(BaseHttpResponse $response)
    {
        $leads = CrmLead::query()
            ->with(['assignedAgent', 'currency'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('stage');

        $pipeline = [];
        foreach (CrmLeadStageEnum::labels() as $value => $label) {
            $pipeline[$value] = $leads->get($value, collect())->values();
        }

        return $response->setData($pipeline);
    }

    public function store(CrmLeadRequest $request, BaseHttpResponse $response)
    {
        $lead = CrmLead::query()->create($request->validated());

        return $response
            ->setMessage('Lead creado correctamente.')
            ->setData($lead->load(['assignedAgent', 'currency']));
    }

    public function update(int|string $id, CrmLeadRequest $request, BaseHttpResponse $response)
    {
        $lead = CrmLead::query()->findOrFail($id);
        $lead->fill($request->validated());
        $lead->save();

        return $response
            ->setMessage('Lead actualizado correctamente.')
            ->setData($lead->load(['assignedAgent', 'currency']));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $lead = CrmLead::query()->findOrFail($id);
            $lead->delete();

            return $response->setMessage('Lead eliminado correctamente.');
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function detail(int|string $id, BaseHttpResponse $response)
    {
        $lead = CrmLead::query()
            ->with(['assignedAgent', 'currency', 'client', 'consult', 'properties', 'activities.user'])
            ->findOrFail($id);

        return $response->setData($lead);
    }

    public function updateStage(int|string $id, Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'stage' => ['required', 'in:' . implode(',', array_keys(CrmLeadStageEnum::labels()))],
        ]);

        $lead = CrmLead::query()->findOrFail($id);
        $lead->update(['stage' => $request->input('stage')]);

        return $response
            ->setMessage('Etapa actualizada.')
            ->setData($lead);
    }

    public function addProperty(int|string $id, Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'property_id' => ['required', 'exists:re_properties,id'],
            'interest_level' => ['nullable', 'in:high,medium,low'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead = CrmLead::query()->findOrFail($id);

        $lead->properties()->syncWithoutDetaching([
            $request->input('property_id') => [
                'interest_level' => $request->input('interest_level', 'medium'),
                'notes' => $request->input('notes'),
            ],
        ]);

        return $response
            ->setMessage('Propiedad agregada al lead.')
            ->setData($lead->load('properties'));
    }

    public function removeProperty(int|string $id, int|string $propertyId, BaseHttpResponse $response)
    {
        $lead = CrmLead::query()->findOrFail($id);
        $lead->properties()->detach($propertyId);

        return $response
            ->setMessage('Propiedad removida del lead.')
            ->setData($lead->load('properties'));
    }

    public function importConsults(BaseHttpResponse $response)
    {
        $consults = Consult::query()
            ->where('status', \Botble\RealEstate\Enums\ConsultStatusEnum::UNREAD)
            ->whereNotExists(function ($query) {
                $query->select('id')
                    ->from('re_crm_leads')
                    ->whereColumn('re_crm_leads.consult_id', 're_consults.id');
            })
            ->get();

        $count = 0;
        foreach ($consults as $consult) {
            CrmLead::query()->create([
                'name' => $consult->name,
                'email' => $consult->email,
                'phone' => $consult->phone,
                'source' => CrmLeadSourceEnum::CONSULT,
                'stage' => CrmLeadStageEnum::NUEVO,
                'consult_id' => $consult->id,
                'notes' => $consult->content,
            ]);

            $consult->update(['status' => \Botble\RealEstate\Enums\ConsultStatusEnum::READ]);
            $count++;
        }

        return $response->setMessage("Se importaron {$count} consultas como leads.");
    }
}
