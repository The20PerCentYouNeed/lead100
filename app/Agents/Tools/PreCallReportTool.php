<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Agents\ProcessingAgent;
use Illuminate\Support\Facades\Log;
use LarAgent\Tool;

final class PreCallReportTool extends Tool
{
    protected string $name = 'generate_pre_call_report';

    protected string $description = 'Generate a comprehensive pre-call report for sales representatives. Analyzes prospect company research, prospect contact details, and seller context to produce actionable talking points, discovery questions, objection handling strategies, and strategic recommendations. Requires company_summary (from research_company), prospect_summary, and seller_context (from research_seller). Optionally accepts seller_notes for additional positioning guidance.';

    protected array $properties = [
        'company_summary' => [
            'type' => 'string',
            'description' => 'The prospect company research summary obtained from the research_company tool. Must contain the full research output including company overview, products, services, and sales-relevant observations.',
        ],
        'prospect_summary' => [
            'type' => 'string',
            'description' => 'Information about the prospect contact extracted from the user\'s message. Include: name, job title, role, company tenure if known, and any relevant background such as previous companies, LinkedIn insights, or notable achievements.',
        ],
        'seller_context' => [
            'type' => 'string',
            'description' => 'The seller company context obtained from the research_seller tool. Contains the seller\'s value proposition, ICP, and positioning information.',
        ],
        'seller_notes' => [
            'type' => 'string',
            'description' => 'Additional context from the user for this specific call. Use for: specific value propositions to emphasize, competitive situations to address, relationship history, or unique angles not captured in the seller research.',
        ],
    ];

    protected array $required = ['company_summary', 'prospect_summary', 'seller_context'];

    public function execute(array $input): string
    {
        Log::info('PreCallReportTool: execute called', [
            'has_company_summary' => isset($input['company_summary']),
            'company_summary_length' => strlen($input['company_summary'] ?? ''),
            'has_prospect_summary' => isset($input['prospect_summary']),
            'prospect_summary_length' => strlen($input['prospect_summary'] ?? ''),
            'has_seller_context' => isset($input['seller_context']),
            'seller_context_length' => strlen($input['seller_context'] ?? ''),
            'has_seller_notes' => isset($input['seller_notes']),
        ]);

        try {
            $prompt = view('agents.processing_agent.pre-call-report', [
                'company_summary' => $input['company_summary'],
                'prospect_summary' => $input['prospect_summary'],
                'seller_context' => $input['seller_context'],
                'seller_notes' => $input['seller_notes'] ?? null,
            ])->render();

            Log::info('PreCallReportTool: Sending to ProcessingAgent', [
                'prompt_length' => strlen($prompt),
            ]);

            $result = ProcessingAgent::make()->respond($prompt);

            Log::info('PreCallReportTool: success', [
                'result_length' => strlen($result),
            ]);

            return $result;
        }
        catch (\Throwable $e) {
            Log::error('PreCallReportTool: error', [
                'error' => $e->getMessage(),
            ]);

            return "Error generating pre-call report: {$e->getMessage()}";
        }
    }
}
