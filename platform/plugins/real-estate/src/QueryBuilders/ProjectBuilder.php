<?php

namespace Botble\RealEstate\QueryBuilders;

use Botble\Base\Models\BaseQueryBuilder;
use Botble\RealEstate\Facades\RealEstateHelper;

class ProjectBuilder extends BaseQueryBuilder
{
    public function active(): self
    {
        $this->where(RealEstateHelper::getProjectDisplayQueryConditions());

        return $this;
    }
}
