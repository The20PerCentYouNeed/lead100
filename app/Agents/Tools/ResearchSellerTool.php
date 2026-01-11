<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Services\WebsiteContextService;
use Illuminate\Support\Facades\Log;
use LarAgent\Tool;

final class ResearchSellerTool extends Tool
{
    protected string $name = 'research_seller';

    protected string $description = 'Research the seller\'s company by analyzing their website. Scrapes the provided URL and returns a structured summary including company identity, value proposition, ideal customer profile, proof points, and positioning insights. Use this tool when the user mentions their own company URL. Results are cached for efficiency.';

    protected array $properties = [
        'seller_url' => [
            'type' => 'string',
            'description' => 'The full URL of the seller\'s company website. This is the user\'s own company, not the prospect. Must be a valid, accessible URL (e.g., https://www.mycompany.com).',
        ],
    ];

    protected array $required = ['seller_url'];

    public function __construct(
        private readonly WebsiteContextService $websiteContextService
    ) {
        parent::__construct($this->name, $this->description);
    }

    public function execute(array $input): string
    {
        Log::info('ResearchSellerTool: execute called', ['input' => $input]);

        try {
            $result = $this->websiteContextService->getSellerContext($input['seller_url']);
            Log::info('ResearchSellerTool: success', [
                'url' => $input['seller_url'],
                'result_length' => strlen($result),
            ]);

            return $result;
        }
        catch (\Throwable $e) {
            Log::error('ResearchSellerTool: error', [
                'url' => $input['seller_url'] ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "Error researching seller company: {$e->getMessage()}. Please verify the URL is correct and accessible.";
        }
    }
}
