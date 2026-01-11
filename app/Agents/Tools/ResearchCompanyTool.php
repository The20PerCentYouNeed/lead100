<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Services\WebsiteContextService;
use Illuminate\Support\Facades\Log;
use LarAgent\Tool;

final class ResearchCompanyTool extends Tool
{
    protected string $name = 'research_company';

    protected string $description = 'Research a prospect company by analyzing their website. Scrapes the provided URL and returns a structured summary including company overview, products and services, target customers, value proposition, growth signals, and sales-relevant observations. Results are cached for efficiency.';

    protected array $properties = [
        'company_url' => [
            'type' => 'string',
            'description' => 'The full URL of the prospect company\'s website to research. Must be a valid, accessible URL (e.g., https://www.example.com).',
        ],
    ];

    protected array $required = ['company_url'];

    public function __construct(
        private readonly WebsiteContextService $websiteContextService
    ) {
        parent::__construct($this->name, $this->description);
    }

    public function execute(array $input): string
    {
        Log::info('ResearchCompanyTool: execute called', ['input' => $input]);

        try {
            $result = $this->websiteContextService->getCompanyContext($input['company_url']);
            Log::info('ResearchCompanyTool: success', [
                'url' => $input['company_url'],
                'result_length' => strlen($result),
            ]);

            return $result;
        }
        catch (\Throwable $e) {
            Log::error('ResearchCompanyTool: error', [
                'url' => $input['company_url'] ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "Error researching company: {$e->getMessage()}. Please verify the URL is correct and accessible.";
        }
    }
}
