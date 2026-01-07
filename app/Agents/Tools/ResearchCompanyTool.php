<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\AiAgents\ProcessingAgent;
use App\Services\FirecrawlService;
use LarAgent\Tool;

final class ResearchCompanyTool extends Tool
{
    protected string $name = 'research_company';

    protected string $description = 'Research a company by scraping their website. Takes a company URL and returns an AI-generated summary including industry, size, products, recent news, and key information.';

    protected array $properties = [
        'company_url' => [
            'type' => 'string',
            'description' => 'The full URL of the company website to research (e.g., https://www.noctuacore.ai/)',
        ],
    ];

    protected array $required = ['company_url'];

    public function __construct(
        private readonly FirecrawlService $firecrawlService
    ) {
        parent::__construct($this->name, $this->description);
    }

    public function execute(array $input): string
    {
        $url = $input['company_url'];

        try {
            $data = $this->firecrawlService->scrape($url);

            // Process raw scraped data through LLM for clean, structured summary.
            $prompt = view('agents.processing_agent.research-company', [
                'data' => $data['content'],
            ])->render();

            $processedSummary = ProcessingAgent::make()->respond($prompt);

            // Combine metadata with LLM-processed summary.
            $summary = "Company Research Summary for: {$data['title']}\n\n";
            $summary .= "Website: {$url}\n\n";

            if (!empty($data['metadata']['description'])) {
                $summary .= "Description: {$data['metadata']['description']}\n\n";
            }

            $summary .= $processedSummary;

            return $summary;
        }
        catch (\Throwable $e) {
            return "Error researching company: {$e->getMessage()}. Please verify the URL is correct and accessible.";
        }
    }
}
