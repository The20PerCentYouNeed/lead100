<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Services\LinkedInService;
use LarAgent\Tool;

final class ResearchProspectTool extends Tool
{
    protected string $name = 'research_prospect';

    protected string $description = 'Research a prospect by fetching their LinkedIn profile. Takes a LinkedIn profile URL and returns an AI-generated summary including role, experience, interests, and talking points.';

    protected array $properties = [
        'linkedin_url' => [
            'type' => 'string',
            'description' => 'The full LinkedIn profile URL (e.g., https://www.linkedin.com/in/username/)',
        ],
    ];

    protected array $required = ['linkedin_url'];

    public function __construct(
        private readonly LinkedInService $linkedInService
    ) {
        parent::__construct($this->name, $this->description);
    }

    public function execute(array $input): string
    {
        $url = $input['linkedin_url'];

        try {
            $profile = $this->linkedInService->getProfile($url);

            $summary = "Prospect Research Summary\n\n";
            $summary .= "Name: {$profile['name']}\n";

            if (!empty($profile['headline'])) {
                $summary .= "Headline: {$profile['headline']}\n";
            }

            if (!empty($profile['location'])) {
                $summary .= "Location: {$profile['location']}\n";
            }

            if (!empty($profile['summary'])) {
                $summary .= "\nSummary:\n{$profile['summary']}\n";
            }

            if (!empty($profile['experience'])) {
                $summary .= "\nExperience:\n";
                foreach (array_slice($profile['experience'], 0, 5) as $exp) {
                    $title = $exp['title'] ?? 'Unknown';
                    $company = $exp['company'] ?? 'Unknown';
                    $duration = $exp['duration'] ?? '';
                    $summary .= "- {$title} at {$company}";
                    if ($duration) {
                        $summary .= " ({$duration})";
                    }
                    $summary .= "\n";
                }
            }

            if (!empty($profile['education'])) {
                $summary .= "\nEducation:\n";
                foreach (array_slice($profile['education'], 0, 3) as $edu) {
                    $school = $edu['school'] ?? 'Unknown';
                    $degree = $edu['degree'] ?? '';
                    $summary .= "- {$school}";
                    if ($degree) {
                        $summary .= " - {$degree}";
                    }
                    $summary .= "\n";
                }
            }

            $summary .= "\nLinkedIn Profile: {$profile['profileUrl']}";

            return $summary;
        }
        catch (\Throwable $e) {
            return "Error researching prospect: {$e->getMessage()}. Please verify the LinkedIn URL is correct.";
        }
    }
}
