<?php

namespace Botble\RealEstate\Services;

use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PropertySearchService
{
    public function searchProperties(array $criteria): Collection
    {
        $query = Property::query()
            ->active()
            ->with(['city', 'state', 'currency', 'categories']);

        if (! empty($criteria['keyword'])) {
            $keyword = $criteria['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('re_properties.name', 'LIKE', "%{$keyword}%")
                    ->orWhere('re_properties.location', 'LIKE', "%{$keyword}%")
                    ->orWhere('re_properties.description', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('state', function ($q) use ($keyword) {
                        $q->where('states.name', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('city', function ($q) use ($keyword) {
                        $q->where('cities.name', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('project', function ($q) use ($keyword) {
                        $q->where('re_projects.name', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('categories', function ($q) use ($keyword) {
                        $q->where('re_categories.name', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        if (! empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        if (! empty($criteria['bedrooms'])) {
            $bedrooms = (int) $criteria['bedrooms'];
            if ($bedrooms >= 5) {
                $query->where('number_bedroom', '>=', $bedrooms);
            } else {
                $query->where('number_bedroom', $bedrooms);
            }
        }

        if (! empty($criteria['bathrooms'])) {
            $bathrooms = (int) $criteria['bathrooms'];
            if ($bathrooms >= 5) {
                $query->where('number_bathroom', '>=', $bathrooms);
            } else {
                $query->where('number_bathroom', $bathrooms);
            }
        }

        if (! empty($criteria['min_price'])) {
            $query->where('price', '>=', (float) $criteria['min_price']);
        }

        if (! empty($criteria['max_price'])) {
            $query->where('price', '<=', (float) $criteria['max_price']);
        }

        if (! empty($criteria['location'])) {
            $location = $criteria['location'];
            $query->where(function ($q) use ($location) {
                $q->where('re_properties.name', 'LIKE', "%{$location}%")
                    ->orWhere('re_properties.location', 'LIKE', "%{$location}%")
                    ->orWhereHas('state', function ($q) use ($location) {
                        $q->where('states.name', 'LIKE', "%{$location}%");
                    })
                    ->orWhereHas('city', function ($q) use ($location) {
                        $q->where('cities.name', 'LIKE', "%{$location}%");
                    });
            });
        }

        if (! empty($criteria['category'])) {
            $category = $criteria['category'];
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('re_categories.name', 'LIKE', "%{$category}%");
            });
        }

        $maxResults = (int) (setting('crm_whatsapp_bot_max_properties') ?: 5);

        Log::info('WhatsAppBot: Property search', [
            'criteria' => $criteria,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'total_active' => Property::query()->active()->count(),
        ]);

        return $query->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($maxResults)
            ->get();
    }

    public function getPropertyDetail(int $propertyId): ?Property
    {
        return Property::query()
            ->active()
            ->with(['city', 'state', 'currency', 'categories', 'features', 'project'])
            ->find($propertyId);
    }

    public function formatPropertyForWhatsApp(Property $property): string
    {
        $currency = $property->currency;
        $symbol = $currency ? $currency->symbol : '$';
        $price = $symbol . number_format($property->price, 0, '.', ',');

        $type = $property->type->value === 'sale' ? 'VENTA' : 'ALQUILER';

        $location = '';
        if ($property->city && $property->city->name) {
            $location = $property->city->name;
            if ($property->state && $property->state->name) {
                $location .= ', ' . $property->state->name;
            }
        } elseif ($property->location) {
            $location = $property->location;
        }

        $line = "*{$property->name}* — {$price}\n";
        $line .= "📍 {$location} | {$type}\n";

        $specs = [];
        if ($property->number_bedroom) {
            $specs[] = "🛏️ {$property->number_bedroom} hab";
        }
        if ($property->number_bathroom) {
            $specs[] = "🚿 {$property->number_bathroom} baños";
        }
        if ($property->square) {
            $unit = setting('real_estate_square_unit', 'm²');
            $specs[] = "📐 " . number_format($property->square) . " {$unit}";
        }

        if ($specs) {
            $line .= implode(' | ', $specs);
        }

        return $line;
    }

    public function formatPropertyList(Collection $properties): string
    {
        if ($properties->isEmpty()) {
            return "No encontré propiedades con esos criterios. ¿Querés intentar con otros filtros?";
        }

        $lines = [];
        foreach ($properties->values() as $index => $property) {
            $num = $index + 1;
            $lines[] = "{$num}. " . $this->formatPropertyForWhatsApp($property);
        }

        $text = implode("\n\n", $lines);

        if ($properties->count() > 1) {
            $text .= "\n\n¿Te interesa alguna? Enviame el número para más detalles.";
        }

        return $text;
    }

    public function formatPropertyDetail(Property $property): string
    {
        $basic = $this->formatPropertyForWhatsApp($property);

        $detail = $basic . "\n\n";

        if ($property->description) {
            $desc = strip_tags($property->description);
            if (mb_strlen($desc) > 300) {
                $desc = mb_substr($desc, 0, 300) . '...';
            }
            $detail .= "📝 *Descripción:*\n{$desc}\n\n";
        }

        if ($property->features && $property->features->count()) {
            $featureNames = $property->features->pluck('name')->implode(', ');
            $detail .= "✅ *Características:* {$featureNames}\n\n";
        }

        if ($property->project && $property->project->name) {
            $detail .= "🏗️ *Proyecto:* {$property->project->name}\n\n";
        }

        $detail .= "¿Te gustaría agendar una visita o recibir más información?";

        return $detail;
    }
}
