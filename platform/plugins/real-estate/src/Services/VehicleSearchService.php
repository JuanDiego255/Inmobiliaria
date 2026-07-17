<?php

namespace Botble\RealEstate\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VehicleSearchService
{
    protected function getBaseUrl(): string
    {
        return rtrim(setting('crm_vehicle_api_url') ?: config('services.vehicles_api.url', ''), '/');
    }

    protected function getToken(): string
    {
        return setting('crm_vehicle_api_token') ?: config('services.vehicles_api.token', '');
    }

    protected function client()
    {
        return Http::timeout(15)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getToken(),
                'Accept' => 'application/json',
            ]);
    }

    public function searchVehicles(array $params): array
    {
        $baseUrl = $this->getBaseUrl();
        if (! $baseUrl || ! $this->getToken()) {
            Log::warning('VehicleSearchService: Missing API URL or token');
            return [];
        }

        $query = [];

        if (! empty($params['keyword']) || ! empty($params['q'])) {
            $query['q'] = $params['keyword'] ?? $params['q'];
        }
        if (! empty($params['brand'])) {
            $query['brand'] = $params['brand'];
        }
        if (! empty($params['model'])) {
            $query['model'] = $params['model'];
        }
        if (! empty($params['year'])) {
            $query['year'] = $params['year'];
        }
        if (! empty($params['year_min'])) {
            $query['year_min'] = $params['year_min'];
        }
        if (! empty($params['year_max'])) {
            $query['year_max'] = $params['year_max'];
        }
        if (! empty($params['color'])) {
            $query['color'] = $params['color'];
        }
        if (! empty($params['transmission'])) {
            $query['transmission'] = $params['transmission'];
        }
        if (! empty($params['fuel_type'])) {
            $query['fuel_type'] = $params['fuel_type'];
        }
        if (! empty($params['condition'])) {
            $query['condition'] = $params['condition'];
        }
        if (isset($params['price_min']) && $params['price_min'] !== '') {
            $query['price_min'] = $params['price_min'];
        }
        if (isset($params['price_max']) && $params['price_max'] !== '') {
            $query['price_max'] = $params['price_max'];
        }

        $query['per_page'] = min((int) ($params['per_page'] ?? 5), 10);
        $query['sort'] = $params['sort'] ?? 'price_asc';

        try {
            $response = $this->client()->get("{$baseUrl}/api/v1/vehicles", $query);

            if (! $response->successful()) {
                Log::warning('VehicleSearchService: API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            return $response->json('data', []);
        } catch (\Exception $e) {
            Log::error('VehicleSearchService: Exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getVehicleDetail(int $id): ?array
    {
        $baseUrl = $this->getBaseUrl();
        if (! $baseUrl || ! $this->getToken()) {
            return null;
        }

        try {
            $response = $this->client()->get("{$baseUrl}/api/v1/vehicles/{$id}");

            if (! $response->successful()) {
                return null;
            }

            return $response->json('data');
        } catch (\Exception $e) {
            Log::error('VehicleSearchService: Detail exception', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
