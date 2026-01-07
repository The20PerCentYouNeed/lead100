<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use LarAgent\Tool;

final class LeadQualificationTool extends Tool
{
    protected string $name = 'qualify_lead';

    protected string $description = 'Qualify a lead based on company research, prospect research, and qualification criteria. Analyzes the fit and provides a qualification score, assessment, concerns, and recommendations.';

    protected array $properties = [
        'company_summary' => [
            'type' => 'string',
            'description' => 'The company research summary from the research_company tool',
        ],
        'prospect_summary' => [
            'type' => 'string',
            'description' => 'The prospect research summary from the research_prospect tool',
        ],
        'qualification_criteria' => [
            'type' => 'string',
            'description' => 'The qualification criteria provided by the user (e.g., "Looking for companies in SaaS with 50-200 employees, decision makers in tech roles")',
        ],
    ];

    protected array $required = ['company_summary', 'prospect_summary', 'qualification_criteria'];

    public function execute(array $input): string
    {
        $companySummary = $input['company_summary'];
        $prospectSummary = $input['prospect_summary'];
        $criteria = $input['qualification_criteria'];

        // This tool will be enhanced by the AI agent's reasoning
        // The agent will analyze the summaries against criteria and generate the assessment
        $assessment = "Lead Qualification Assessment\n\n";
        $assessment .= "Qualification Criteria:\n{$criteria}\n\n";
        $assessment .= "Company Summary:\n{$companySummary}\n\n";
        $assessment .= "Prospect Summary:\n{$prospectSummary}\n\n";
        $assessment .= "Please analyze the above information against the qualification criteria and provide:\n";
        $assessment .= "1. Qualification Score (0-100)\n";
        $assessment .= "2. Fit Assessment (High/Medium/Low)\n";
        $assessment .= "3. Key Strengths\n";
        $assessment .= "4. Concerns or Gaps\n";
        $assessment .= "5. Recommendations for Next Steps";

        return $assessment;
    }
}
