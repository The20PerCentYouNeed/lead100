<?php

declare(strict_types=1);

namespace App\Agents;

use App\Agents\Tools\LeadQualificationTool;
use App\Agents\Tools\PreCallReportTool;
use App\Agents\Tools\ResearchCompanyTool;
use App\Agents\Tools\ResearchSellerTool;
use App\Services\WebsiteContextService;
use LarAgent\Agent;

final class LeadAgent extends Agent
{
    protected $history = 'cache';

    protected $provider = 'default';

    public function instructions()
    {
        return view('agents.lead_agent.instructions')->render();
    }

    public function registerTools(): array
    {
        $websiteContextService = app(WebsiteContextService::class);

        return [
            new ResearchCompanyTool($websiteContextService),
            new ResearchSellerTool($websiteContextService),
            new LeadQualificationTool(),
            new PreCallReportTool(),
        ];
    }

    public function setProvider(string $provider): self
    {
        $this->changeProvider($provider);

        return $this;
    }
}
