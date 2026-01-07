<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class LinkedInService
{
    private string $apiKey;
    private string $apiHost;

    public function __construct()
    {
        $this->apiKey = config('services.rapidapi.key', env('RAPIDAPI_KEY'));
        $this->apiHost = config('services.rapidapi.linkedin_host', env('RAPIDAPI_LINKEDIN_HOST', 'linkedin-data-api.p.rapidapi.com'));
    }

    /**
     * Get LinkedIn profile data from a LinkedIn URL
     *
     * @return array{name: string, headline: string, experience: array<int, array<string, mixed>>, education: array<int, array<string, mixed>>, summary: string, location: string}
     */
    public function getProfile(string $linkedInUrl): array
    {
        try {
            // Extract LinkedIn username/profile identifier from URL
            $profileId = $this->extractProfileId($linkedInUrl);

            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => $this->apiHost,
            ])->get("https://{$this->apiHost}/profile/{$profileId}");

            if (!$response->successful()) {
                Log::error('LinkedIn API error', [
                    'url' => $linkedInUrl,
                    'profileId' => $profileId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \RuntimeException("Failed to fetch LinkedIn profile: {$linkedInUrl}. Status: {$response->status()}");
            }

            $data = $response->json();

            return [
                'name' => $data['name'] ?? '',
                'headline' => $data['headline'] ?? '',
                'experience' => $data['experience'] ?? [],
                'education' => $data['education'] ?? [],
                'summary' => $data['summary'] ?? '',
                'location' => $data['location'] ?? '',
                'profileUrl' => $linkedInUrl,
            ];
        }
        catch (\Throwable $e) {
            Log::error('LinkedIn service error', [
                'url' => $linkedInUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Error fetching LinkedIn profile: {$linkedInUrl}. {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Extract profile identifier from LinkedIn URL
     */
    private function extractProfileId(string $url): string
    {
        // Handle various LinkedIn URL formats:
        // https://www.linkedin.com/in/username/
        // https://www.linkedin.com/in/username
        // https://linkedin.com/in/username/
        $pattern = '/linkedin\.com\/in\/([^\/\?]+)/i';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        throw new \InvalidArgumentException("Invalid LinkedIn URL format: {$url}");
    }
}
