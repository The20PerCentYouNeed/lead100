<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use LarAgent\Tool;

final class PreCallReportTool extends Tool
{
    protected string $name = 'generate_pre_call_report';

    protected string $description = 'Generate a comprehensive pre-call report for sales representatives. Takes company and prospect summaries and creates a formatted report with talking points, questions to ask, potential objections, and strategic recommendations.';

    protected array $properties = [
        'company_summary' => [
            'type' => 'string',
            'description' => 'The company research summary from the research_company tool',
        ],
        'prospect_summary' => [
            'type' => 'string',
            'description' => 'The prospect research summary from the research_prospect tool',
        ],
    ];

    protected array $required = ['company_summary', 'prospect_summary'];

    public function execute(array $input): string
    {
        $companySummary = $input['company_summary'];
        $prospectSummary = $input['prospect_summary'];

        // This tool provides structured data for the AI agent to format into a report
        $reportData = "Pre-Call Report Data\n\n";
        $reportData .= "Company Information:\n{$companySummary}\n\n";
        $reportData .= "Prospect Information:\n{$prospectSummary}\n\n";
        $reportData .= "Please generate a comprehensive pre-call report including:\n";
        $reportData .= "1. Executive Summary\n";
        $reportData .= "2. Key Talking Points\n";
        $reportData .= "3. Questions to Ask\n";
        $reportData .= "4. Potential Objections and Responses\n";
        $reportData .= "5. Strategic Recommendations\n";
        $reportData .= "6. Next Steps";

        return $reportData;
    }
}
