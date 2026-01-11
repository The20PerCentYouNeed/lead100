<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching LinkedIn data via Real-Time LinkedIn Scraper API
 *
 * @see https://rapidapi.com/rockapis-rockapis-default/api/linkedin-data-api
 */
final class LinkedInService
{
    private string $apiKey;

    private string $apiHost;

    private const TIMEOUT_SECONDS = 30;

    public function __construct()
    {
        $this->apiKey = config('services.rapidapi.key');
        $this->apiHost = config(
            'services.rapidapi.linkedin_host',
            'linkedin-data-api.p.rapidapi.com'
        );
    }

    /**
     * Get LinkedIn profile data from a LinkedIn URL
     *
     * @return array{
     *     name: string,
     *     headline: string,
     *     experience: array<int, array<string, mixed>>,
     *     education: array<int, array<string, mixed>>,
     *     summary: string,
     *     location: string,
     *     profileUrl: string,
     *     skills: array<int, string>,
     *     profilePicture: string|null
     * }
     */
    public function getProfile(string $linkedInUrl): array
    {
        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => $this->apiHost,
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->get("https://{$this->apiHost}/get-profile-data-by-url", [
                    'url' => $linkedInUrl,
                ]);

            if (!$response->successful()) {
                $this->handleError('profile', $linkedInUrl, $response);
            }

            $data = $response->json();

            return $this->parseProfileResponse($data, $linkedInUrl);
        }
        catch (\Throwable $e) {
            Log::error('LinkedIn service error', [
                'url' => $linkedInUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "Error fetching LinkedIn profile: {$linkedInUrl}. {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Get LinkedIn company data from a domain
     *
     * @return array{
     *     name: string,
     *     description: string,
     *     industry: string,
     *     companySize: string,
     *     headquarters: string,
     *     website: string|null,
     *     founded: string|null,
     *     specialties: array<int, string>,
     *     companyUrl: string
     * }
     */
    public function getCompanyByDomain(string $domain): array
    {
        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => $this->apiHost,
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->get("https://{$this->apiHost}/get-company-by-domain", [
                    'domain' => $domain,
                ]);

            if (!$response->successful()) {
                $this->handleError('company by domain', $domain, $response);
            }

            $data = $response->json();

            return $this->parseCompanyResponse($data, $domain);
        }
        catch (\Throwable $e) {
            Log::error('LinkedIn company by domain service error', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "Error fetching company by domain: {$domain}. {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Handle API error responses
     */
    private function handleError(string $type, string $identifier, Response $response): never
    {
        $status = $response->status();
        $body = $response->body();

        Log::error("LinkedIn API error ({$type})", [
            'identifier' => $identifier,
            'status' => $status,
            'body' => $body,
        ]);

        $message = match ($status) {
            401 => 'Invalid API key',
            403 => 'Access forbidden - check your RapidAPI subscription',
            404 => 'Profile or company not found',
            429 => 'Rate limit exceeded - too many requests',
            500, 502, 503 => 'LinkedIn API server error - please try again later',
            default => "HTTP {$status}",
        };

        throw new \RuntimeException(
            "Failed to fetch LinkedIn {$type}: {$identifier}. {$message}"
        );
    }

    /**
     * Parse profile API response into structured array
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function parseProfileResponse(array $data, string $linkedInUrl): array
    {
        return [
            'name' => $data['fullName'] ?? $data['full_name'] ?? $data['name'] ?? '',
            'headline' => $data['headline'] ?? '',
            'experience' => $data['position'] ?? $data['positions'] ?? $data['experience'] ?? [],
            'education' => $data['educations'] ?? $data['education'] ?? [],
            'summary' => $data['summary'] ?? $data['about'] ?? '',
            'location' => $data['geo']['full'] ?? $data['location'] ?? '',
            'profileUrl' => $linkedInUrl,
            'skills' => $data['skills'] ?? [],
            'profilePicture' => $data['profilePicture'] ?? $data['profile_picture'] ?? null,
        ];
    }

    /**
     * Parse company API response into structured array
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function parseCompanyResponse(array $data, string $identifier): array
    {
        return [
            'name' => $data['name'] ?? $data['companyName'] ?? '',
            'description' => $data['description'] ?? $data['about'] ?? '',
            'industry' => $data['industry'] ?? '',
            'companySize' => $data['companySize'] ?? $data['staffCount'] ?? '',
            'headquarters' => $data['headquarter']['city'] ?? $data['headquarters'] ?? '',
            'website' => $data['website'] ?? null,
            'founded' => $data['founded'] ?? $data['foundedOn'] ?? null,
            'specialties' => $data['specialities'] ?? $data['specialties'] ?? [],
            'companyUrl' => $data['linkedInUrl'] ?? $data['linkedin_url'] ?? $identifier,
            'logo' => $data['logo'] ?? null,
        ];
    }
}
