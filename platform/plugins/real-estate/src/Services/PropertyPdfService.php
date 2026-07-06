<?php

namespace Botble\RealEstate\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Log;

class PropertyPdfService
{
    public function generatePropertyPdf(Property $property): ?string
    {
        try {
            $property->loadMissing(['city', 'state', 'currency', 'categories', 'features', 'project']);

            $data = $this->preparePropertyData($property);
            $html = $this->buildPdfHtml($data);

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('letter', 'portrait');

            $filename = 'propiedad_' . $property->id . '_' . time() . '.pdf';
            $path = storage_path('app/public/pdf/' . $filename);

            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdf->save($path);

            return url('storage/pdf/' . $filename);
        } catch (\Exception $e) {
            Log::error('PropertyPdfService: Failed to generate PDF', [
                'property_id' => $property->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function preparePropertyData(Property $property): array
    {
        $currency = $property->currency;
        $symbol = $currency ? $currency->symbol : '$';

        $typeValue = $property->type instanceof \Botble\Base\Supports\Enum ? $property->type->getValue() : (string) $property->type;

        $location = '';
        if ($property->city && $property->city->name) {
            $location = $property->city->name;
            if ($property->state && $property->state->name) {
                $location .= ', ' . $property->state->name;
            }
        } elseif ($property->location) {
            $location = $property->location;
        }

        $imageUrl = null;
        if ($property->image) {
            $imageUrl = RvMedia::getImageUrl($property->image, 'medium');
            if ($imageUrl && ! str_starts_with($imageUrl, 'http')) {
                $imageUrl = url($imageUrl);
            }
        }

        return [
            'id' => $property->id,
            'name' => $property->name,
            'type' => $typeValue === 'sale' ? 'Venta' : 'Alquiler',
            'price' => $symbol . number_format($property->price, 0, '.', ','),
            'location' => $location,
            'bedrooms' => $property->number_bedroom,
            'bathrooms' => $property->number_bathroom,
            'square' => $property->square ? number_format($property->square) . ' ' . setting('real_estate_square_unit', 'm²') : null,
            'description' => $property->description ? strip_tags($property->description) : '',
            'features' => $property->features ? $property->features->pluck('name')->toArray() : [],
            'category' => $property->categories->first()?->name,
            'project' => $property->project?->name,
            'image_url' => $imageUrl,
        ];
    }

    protected function buildPdfHtml(array $data): string
    {
        $features = '';
        if (! empty($data['features'])) {
            $featureItems = '';
            foreach ($data['features'] as $f) {
                $featureItems .= "<span style=\"display:inline-block;background:#f0f4ff;color:#4338ca;padding:4px 10px;border-radius:12px;font-size:11px;margin:2px 4px 2px 0\">{$f}</span>";
            }
            $features = "<div style=\"margin-top:16px\"><strong style=\"font-size:13px;color:#374151\">Características</strong><div style=\"margin-top:6px\">{$featureItems}</div></div>";
        }

        $imageHtml = '';
        if ($data['image_url']) {
            $imageHtml = "<div style=\"text-align:center;margin-bottom:16px\"><img src=\"{$data['image_url']}\" style=\"max-width:100%;max-height:300px;border-radius:8px\" /></div>";
        }

        $description = '';
        if ($data['description']) {
            $desc = mb_substr($data['description'], 0, 800);
            $description = "<div style=\"margin-top:16px\"><strong style=\"font-size:13px;color:#374151\">Descripción</strong><p style=\"font-size:12px;color:#6b7280;line-height:1.6;margin-top:6px\">{$desc}</p></div>";
        }

        $specs = [];
        if ($data['bedrooms']) {
            $specs[] = "<div style=\"text-align:center;padding:10px\"><div style=\"font-size:22px;font-weight:700;color:#4338ca\">{$data['bedrooms']}</div><div style=\"font-size:11px;color:#6b7280\">Habitaciones</div></div>";
        }
        if ($data['bathrooms']) {
            $specs[] = "<div style=\"text-align:center;padding:10px\"><div style=\"font-size:22px;font-weight:700;color:#4338ca\">{$data['bathrooms']}</div><div style=\"font-size:11px;color:#6b7280\">Baños</div></div>";
        }
        if ($data['square']) {
            $specs[] = "<div style=\"text-align:center;padding:10px\"><div style=\"font-size:22px;font-weight:700;color:#4338ca\">{$data['square']}</div><div style=\"font-size:11px;color:#6b7280\">Área</div></div>";
        }

        $specsHtml = '';
        if ($specs) {
            $specsHtml = "<div style=\"display:flex;justify-content:center;gap:30px;background:#f9fafb;border-radius:8px;padding:12px;margin-top:16px\">" . implode('', $specs) . "</div>";
        }

        $extraInfo = '';
        if ($data['category']) {
            $extraInfo .= "<span style=\"font-size:12px;color:#6b7280\">Categoría: <strong>{$data['category']}</strong></span> ";
        }
        if ($data['project']) {
            $extraInfo .= "<span style=\"font-size:12px;color:#6b7280\">| Proyecto: <strong>{$data['project']}</strong></span>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; margin: 0; padding: 30px; color: #1f2937; font-size: 13px; }
        .header { text-align: center; border-bottom: 2px solid #4338ca; padding-bottom: 16px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; margin: 0; color: #4338ca; }
        .header p { margin: 4px 0 0; color: #6b7280; font-size: 12px; }
        .badge { display: inline-block; background: #4338ca; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .price { font-size: 24px; font-weight: 700; color: #059669; margin: 12px 0 4px; }
        .location { font-size: 13px; color: #6b7280; }
        .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 12px; text-align: center; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ficha Técnica</h1>
        <p>Propiedad #{$data['id']}</p>
    </div>

    {$imageHtml}

    <div style="text-align:center">
        <span class="badge">{$data['type']}</span>
        <h2 style="font-size:18px;margin:12px 0 4px;color:#111827">{$data['name']}</h2>
        <div class="price">{$data['price']}</div>
        <div class="location">{$data['location']}</div>
        {$extraInfo}
    </div>

    {$specsHtml}
    {$description}
    {$features}

    <div class="footer">
        Documento generado automáticamente · {$this->currentDate()}
    </div>
</body>
</html>
HTML;
    }

    protected function currentDate(): string
    {
        return now()->format('d/m/Y H:i');
    }
}
