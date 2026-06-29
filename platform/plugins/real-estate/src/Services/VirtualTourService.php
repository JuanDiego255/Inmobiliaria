<?php

namespace Botble\RealEstate\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VirtualTourService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.tours.url', ''), '/');
        $this->token = config('services.tours.token', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->token);
    }

    public function listTours(array $params = []): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get($this->baseUrl . '/api/v1/tours', array_filter([
                    'q' => $params['q'] ?? null,
                    'category_id' => $params['category_id'] ?? null,
                    'sector_id' => $params['sector_id'] ?? null,
                    'per_page' => $params['per_page'] ?? 12,
                    'page' => $params['page'] ?? 1,
                ]));

            if ($response->successful()) {
                $json = $response->json();
                // DEBUG: remover después de verificar
                logger()->info('VirtualTourService response', ['json' => $json]);
                return $json;
            }

            Log::warning('VirtualTourService: API returned ' . $response->status());

            return null;
        } catch (\Exception $e) {
            Log::error('VirtualTourService: ' . $e->getMessage());

            return null;
        }
    }

    public function getTour(int $id): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->get($this->baseUrl . '/api/v1/tours/' . $id);

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('VirtualTourService: ' . $e->getMessage());

            return null;
        }
    }
}
