<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Http\Requests\CrmActivityRequest;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CrmActivityController extends BaseController
{
    public function store(CrmActivityRequest $request, BaseHttpResponse $response)
    {
        $activity = CrmActivity::query()->create(array_merge(
            $request->validated(),
            ['user_id' => auth()->id()]
        ));

        CrmLead::query()
            ->where('id', $request->input('lead_id'))
            ->update(['last_contacted_at' => Carbon::now()]);

        return $response
            ->setMessage('Actividad registrada correctamente.')
            ->setData($activity->load(['user', 'lead']));
    }

    public function listForLead(int|string $id, BaseHttpResponse $response)
    {
        $activities = CrmActivity::query()
            ->where('lead_id', $id)
            ->with('user')
            ->latest()
            ->get();

        return $response->setData($activities);
    }

    public function markComplete(int|string $id, BaseHttpResponse $response)
    {
        $activity = CrmActivity::query()->findOrFail($id);
        $activity->update(['completed_at' => Carbon::now()]);

        return $response
            ->setMessage('Actividad completada.')
            ->setData($activity);
    }
}
