<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Agents\ProcessingAgent;
use LarAgent\Tool;

final class LeadQualificationTool extends Tool
{
    protected string $name = 'qualify_lead';

    protected string $description = 'Qualify a prospect by comparing their company profile against the seller\'s ideal customer profile. Analyzes fit based on industry, company size, problem/solution alignment, and buying signals. Requires company_summary (from research_company) and seller_context (from research_seller). Returns a structured assessment with qualification score (0-100), fit rating, strengths, concerns, and actionable recommendations.';

    protected array $properties = [
        'company_summary' => [
            'type' => 'string',
            'description' => 'The prospect company research summary obtained from the research_company tool. Must contain company overview, products, target customers, and growth signals.',
        ],
        'seller_context' => [
            'type' => 'string',
            'description' => 'The seller company context obtained from the research_seller tool. Contains the seller\'s value proposition, target market, and ICP indicators used for qualification comparison.',
        ],
        'prospect_summary' => [
            'type' => 'string',
            'description' => 'Information about the prospect contact if available. Helps assess decision-maker fit and accessibility, but is not required for company-level qualification.',
        ],
        'seller_notes' => [
            'type' => 'string',
            'description' => 'Additional qualification criteria or hard requirements from the user. Examples: minimum company size thresholds, required industries, budget indicators, geographic restrictions, or specific use cases that must be present.',
        ],
    ];

    protected array $required = ['company_summary', 'seller_context'];

    public function execute(array $input): string
    {
        try {
            $prompt = view('agents.processing_agent.qualify-lead', [
                'company_summary' => $input['company_summary'],
                'seller_context' => $input['seller_context'],
                'prospect_summary' => $input['prospect_summary'] ?? null,
                'seller_notes' => $input['seller_notes'] ?? null,
            ])->render();

            return ProcessingAgent::make()->respond($prompt);
        }
        catch (\Throwable $e) {
            return "Error qualifying lead: {$e->getMessage()}";
        }
    }
}
