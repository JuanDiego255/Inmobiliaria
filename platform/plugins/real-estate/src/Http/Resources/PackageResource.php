<?php

namespace Botble\RealEstate\Http\Resources;

use Botble\RealEstate\Models\Package;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Package
 */
class PackageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'price_text' => $this->price_text,
            'price_per_post_text' => $this->price_per_post_text,
            'percent_save' => $this->percent_save,
            'number_of_listings' => $this->number_of_listings,
            'number_posts_free' => $this->number_posts_free,
            'price_text_with_sale_off' => $this->price_text_with_sale_off,
        ];
    }
}
