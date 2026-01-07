<?php

declare(strict_types=1);

namespace App\Agents;

use App\Agents\Tools\LeadQualificationTool;
use App\Agents\Tools\PreCallReportTool;
use App\Agents\Tools\ResearchCompanyTool;
use App\Agents\Tools\ResearchProspectTool;
use App\Services\FirecrawlService;
use App\Services\LinkedInService;
use LarAgent\Agent;

final class LeadAgent extends Agent
{
    protected string $history = 'cache';

    protected string $provider = 'default';

    public function instructions()
    {
        return view('lead_agent.instructions')->render();
    }

    /**
     * Register tools with dependency injection
     */
    public function registerTools(): array
    {
        $firecrawlService = app(FirecrawlService::class);
        $linkedInService = app(LinkedInService::class);

        return [
            new ResearchCompanyTool($firecrawlService),
            new ResearchProspectTool($linkedInService),
            new LeadQualificationTool(),
            new PreCallReportTool(),
        ];
    }

    /**
     * Set the provider for this agent instance
     */
    public function setProvider(string $provider): self
    {
        // Call protected method directly - accessible from child class.
        $this->changeProvider($provider);

        return $this;
    }
}
