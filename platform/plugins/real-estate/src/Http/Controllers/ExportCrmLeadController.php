<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Exports\CrmLeadsExport;
use Maatwebsite\Excel\Excel;

class ExportCrmLeadController extends BaseController
{
    public function download(CrmLeadsExport $crmLeadsExport)
    {
        BaseHelper::maximumExecutionTimeAndMemoryLimit();

        return $crmLeadsExport->download('export_crm_leads.csv', Excel::CSV, ['Content-Type' => 'text/csv']);
    }
}
