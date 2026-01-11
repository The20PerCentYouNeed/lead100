<?php

declare(strict_types=1);

namespace App\Services;

use App\Agents\ProcessingAgent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class WebsiteContextService
{
    public function __construct(
        private readonly FirecrawlService $firecrawlService,
    ) {
    }

    /**
     * Get prospect company context from URL with caching.
     * Optimized for understanding the prospect's business, challenges, and opportunities.
     */
    public function getCompanyContext(string $url): string
    {
        Log::info('WebsiteContextService: Getting company context', ['url' => $url]);

        $cacheKey = 'website_context:company:' . md5($url);
        $ttl = now()->addDays(config('services.website_context.cache_ttl_days', 7));

        return Cache::remember($cacheKey, $ttl, function () use ($url) {
            Log::info('WebsiteContextService: Cache miss, scraping company URL', ['url' => $url]);

            $data = $this->firecrawlService->scrape($url);

            Log::info('WebsiteContextService: Firecrawl returned data', [
                'url' => $url,
                'title' => $data['title'] ?? 'N/A',
                'content_length' => strlen($data['content'] ?? ''),
            ]);

            $prompt = view('agents.processing_agent.research-company', [
                'data' => $data['content'],
                'url' => $url,
                'title' => $data['title'] ?? '',
                'description' => $data['metadata']['description'] ?? '',
            ])->render();

            Log::info('WebsiteContextService: Sending to ProcessingAgent', [
                'url' => $url,
                'prompt_length' => strlen($prompt),
            ]);

            $result = ProcessingAgent::make()->respond($prompt);

            Log::info('WebsiteContextService: ProcessingAgent returned', [
                'url' => $url,
                'result_length' => strlen($result),
            ]);

            return $result;
        });
    }

    /**
     * Get seller company context from URL with caching.
     * Optimized for extracting value proposition, ICP, and sales positioning.
     */
    public function getSellerContext(string $url): string
    {
        Log::info('WebsiteContextService: Getting seller context', ['url' => $url]);

        $cacheKey = 'website_context:seller:' . md5($url);
        $ttl = now()->addDays(config('services.website_context.cache_ttl_days', 7));

        return Cache::remember($cacheKey, $ttl, function () use ($url) {
            Log::info('WebsiteContextService: Cache miss, scraping seller URL', ['url' => $url]);

            $data = $this->firecrawlService->scrape($url);

            Log::info('WebsiteContextService: Firecrawl returned data for seller', [
                'url' => $url,
                'title' => $data['title'] ?? 'N/A',
                'content_length' => strlen($data['content'] ?? ''),
            ]);

            $prompt = view('agents.processing_agent.research-seller', [
                'data' => $data['content'],
                'url' => $url,
                'title' => $data['title'] ?? '',
                'description' => $data['metadata']['description'] ?? '',
            ])->render();

            Log::info('WebsiteContextService: Sending seller context to ProcessingAgent', [
                'url' => $url,
                'prompt_length' => strlen($prompt),
            ]);

            $result = ProcessingAgent::make()->respond($prompt);

            Log::info('WebsiteContextService: ProcessingAgent returned seller context', [
                'url' => $url,
                'result_length' => strlen($result),
            ]);

            return $result;
        });
    }

    /**
     * Clear cached context for a specific URL.
     */
    public function clearCache(string $url, ?string $type = null): void
    {
        if ($type === null || $type === 'company') {
            Cache::forget('website_context:company:' . md5($url));
        }
        if ($type === null || $type === 'seller') {
            Cache::forget('website_context:seller:' . md5($url));
        }
    }
}
