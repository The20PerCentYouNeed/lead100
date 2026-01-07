<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FirecrawlService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.firecrawl.api_key', env('FIRECRAWL_API_KEY'));
        $this->apiUrl = config('services.firecrawl.api_url', env('FIRECRAWL_API_URL', 'https://api.firecrawl.dev/v1'));
    }

    /**
     * Scrape a company website and return structured data
     *
     * @return array{content: string, title: string, metadata: array<string, mixed>}
     */
    public function scrape(string $url): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/scrape", [
                'url' => $url,
                'formats' => ['markdown', 'html'],
            ]);

            if (!$response->successful()) {
                Log::error('Firecrawl API error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException("Failed to scrape URL: {$url}. Status: {$response->status()}");
            }

            $data = $response->json();

            return [
                'content' => $data['data']['markdown'] ?? $data['data']['html'] ?? '',
                'title' => $data['data']['metadata']['title'] ?? '',
                'metadata' => [
                    'description' => $data['data']['metadata']['description'] ?? null,
                    'ogTitle' => $data['data']['metadata']['ogTitle'] ?? null,
                    'ogDescription' => $data['data']['metadata']['ogDescription'] ?? null,
                    'ogImage' => $data['data']['metadata']['ogImage'] ?? null,
                    'canonicalUrl' => $data['data']['metadata']['canonicalUrl'] ?? $url,
                ],
            ];
        }
        catch (\Throwable $e) {
            Log::error('Firecrawl service error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Error scraping URL: {$url}. {$e->getMessage()}", 0, $e);
        }
    }
}
