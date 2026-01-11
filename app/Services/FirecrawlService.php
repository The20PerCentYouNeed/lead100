<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class FirecrawlService
{
    private string $apiKey;

    private string $apiUrl;

    private const TIMEOUT_SECONDS = 30;

    private const MAX_AGE_MS = 172800000; // 2 days cache (default)

    public function __construct()
    {
        $this->apiKey = config('services.firecrawl.api_key');
        $this->apiUrl = config('services.firecrawl.api_url', 'https://api.firecrawl.dev/v2');
    }

    /**
     * Scrape a company website and return structured data
     *
     * Uses Firecrawl v2 API with improved defaults:
     * - onlyMainContent: true (excludes headers, footers, navs)
     * - blockAds: true (blocks ads and cookie popups)
     * - removeBase64Images: true (prevents overwhelming output)
     *
     * @see https://docs.firecrawl.dev/api-reference/endpoint/scrape
     *
     * @param  array<string, mixed>  $options
     * @return array{content: string, summary: string|null, title: string, metadata: array<string, mixed>}
     */
    public function scrape(string $url, array $options = []): array
    {
        try {
            $payload = array_merge([
                'url' => $url,
                'formats' => ['markdown'],
                'onlyMainContent' => true,
                'maxAge' => self::MAX_AGE_MS,
                'timeout' => self::TIMEOUT_SECONDS * 1000,
            ], $options);

            /** @var Response $response */
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
                ->timeout(self::TIMEOUT_SECONDS + 10)
                ->post("{$this->apiUrl}/scrape", $payload);

            if (!$response->successful()) {
                $this->handleError($url, $response);
            }

            $data = $response->json();

            if (!($data['success'] ?? false)) {
                throw new \RuntimeException(
                    "Firecrawl returned unsuccessful response for: {$url}"
                );
            }

            return $this->parseResponse($data, $url);
        }
        catch (\Throwable $e) {
            Log::error('Firecrawl service error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "Error scraping URL: {$url}. {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Scrape with AI-generated summary (uses additional credits)
     *
     * @param  array<string, mixed>  $options
     * @return array{content: string, summary: string|null, title: string, metadata: array<string, mixed>}
     */
    public function scrapeWithSummary(string $url, array $options = []): array
    {
        return $this->scrape($url, array_merge($options, [
            'formats' => ['markdown', 'summary'],
        ]));
    }

    /**
     * Handle API error responses
     */
    private function handleError(string $url, Response $response): never
    {
        $status = $response->status();
        $body = $response->body();

        Log::error('Firecrawl API error', [
            'url' => $url,
            'status' => $status,
            'body' => $body,
        ]);

        $message = match ($status) {
            402 => 'Payment required - insufficient Firecrawl credits',
            429 => 'Rate limit exceeded - too many requests',
            500 => 'Firecrawl server error - please try again later',
            default => "HTTP {$status}",
        };

        throw new \RuntimeException("Failed to scrape URL: {$url}. {$message}");
        }

    /**
     * Parse the Firecrawl API response into a structured array
     *
     * @param  array<string, mixed>  $data
     * @return array{content: string, summary: string|null, title: string, metadata: array<string, mixed>}
     */
    private function parseResponse(array $data, string $url): array
    {
        $scraped = $data['data'] ?? [];
        $metadata = $scraped['metadata'] ?? [];

        return [
            'content' => $scraped['markdown'] ?? $scraped['html'] ?? '',
            'summary' => $scraped['summary'] ?? null,
            'title' => $metadata['title'] ?? '',
            'metadata' => [
                'description' => $metadata['description'] ?? null,
                'language' => $metadata['language'] ?? null,
                'sourceURL' => $metadata['sourceURL'] ?? $url,
                'keywords' => $metadata['keywords'] ?? null,
                'statusCode' => $metadata['statusCode'] ?? null,
            ],
        ];
    }
}
