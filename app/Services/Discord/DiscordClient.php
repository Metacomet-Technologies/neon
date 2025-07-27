<?php

declare(strict_types=1);

namespace App\Services\Discord;

use App\Models\User;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Simple Discord HTTP client with rate limiting and error handling.
 */
final class DiscordClient
{
    private const API_BASE = 'https://discord.com/api/v10';
    private const MAX_RETRIES = 3;
    private const RATE_LIMIT_KEY = 'discord_rate_limit';
    private const RATE_LIMIT_STATS_KEY = 'discord_rate_limit_stats';
    private const CIRCUIT_BREAKER_KEY = 'discord_circuit_breaker';
    private const CIRCUIT_BREAKER_THRESHOLD = 5;

    public function __construct(
        private readonly ?string $token = null,
        private readonly bool $isUserToken = false
    ) {}

    /**
     * Create client for user token.
     */
    public static function forUser(User $user): self
    {
        return new self($user->access_token, true);
    }

    /**
     * Get rate limit statistics.
     */
    public static function getRateLimitStats(): array
    {
        $stats = Cache::get(self::RATE_LIMIT_STATS_KEY, [
            'requests' => 0,
            'errors' => 0,
            'rate_limited' => 0,
            'last_reset' => now()->timestamp,
        ]);

        $circuitBreakerState = self::getCircuitBreakerState();

        // Calculate percentages
        $globalUsagePercentage = 0;
        if (isset($stats['limit']) && $stats['limit'] > 0 && isset($stats['remaining'])) {
            $used = $stats['limit'] - $stats['remaining'];
            $globalUsagePercentage = ($used / $stats['limit']) * 100;
        }

        $invalidRequestsPercentage = 0;
        if ($stats['requests'] > 0) {
            $invalidRequestsPercentage = ($stats['errors'] / $stats['requests']) * 100;
        }

        return [
            'circuit_breaker_state' => $circuitBreakerState,
            'global_usage_percentage' => round($globalUsagePercentage, 2),
            'invalid_requests_percentage' => round($invalidRequestsPercentage, 2),
            'remaining_requests' => $stats['remaining'] ?? 0,
            'reset_after' => $stats['reset_after'] ?? 0,
            'total_requests' => $stats['requests'],
            'error_count' => $stats['errors'],
            'rate_limited_count' => $stats['rate_limited'],
            'limit' => $stats['limit'] ?? 0,
            'global' => $stats['global'] ?? false,
        ];
    }

    /**
     * Get circuit breaker state.
     */
    private static function getCircuitBreakerState(): string
    {
        $breaker = Cache::get(self::CIRCUIT_BREAKER_KEY, [
            'failures' => 0,
            'last_failure' => null,
            'state' => 'closed',
        ]);

        // Auto-close after 60 seconds
        if ($breaker['state'] === 'open' && $breaker['last_failure']) {
            if (now()->diffInSeconds($breaker['last_failure']) > 60) {
                $breaker['state'] = 'half-open';
                $breaker['failures'] = 0;
                Cache::put(self::CIRCUIT_BREAKER_KEY, $breaker, 3600);
            }
        }

        return $breaker['state'];
    }

    /**
     * Make a GET request.
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make a POST request.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make a PUT request.
     */
    public function put(string $endpoint, array $data = []): bool
    {
        $response = $this->makeRequest('PUT', $endpoint, ['json' => $data]);

        return $response->successful();
    }

    /**
     * Make a PATCH request.
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, ['json' => $data]);
    }

    /**
     * Make a DELETE request.
     */
    public function delete(string $endpoint): bool
    {
        $response = $this->makeRequest('DELETE', $endpoint);

        return $response->successful();
    }

    /**
     * Make request and return JSON data.
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $response = $this->makeRequest($method, $endpoint, $data);

        if ($response->failed()) {
            throw new Exception("Discord API error: {$response->status()}", $response->status());
        }

        return $response->json() ?: [];
    }

    /**
     * Make HTTP request with retries and rate limiting.
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): Response
    {
        $url = self::API_BASE . '/' . ltrim($endpoint, '/');
        $token = $this->token ?? config('discord.token');

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $this->checkRateLimit();

                $client = Http::timeout(30);
                $tokenType = $this->isUserToken ? 'Bearer' : 'Bot';
                $client = $client->withToken($token, $tokenType);

                $response = match (strtoupper($method)) {
                    'GET' => $client->get($url, $data['query'] ?? []),
                    'POST' => $client->post($url, $data['json'] ?? []),
                    'PUT' => $client->put($url, $data['json'] ?? []),
                    'PATCH' => $client->patch($url, $data['json'] ?? []),
                    'DELETE' => $client->delete($url),
                    default => throw new Exception("Unsupported method: {$method}"),
                };

                $this->handleRateLimit($response);

                if ($response->status() === 429) {
                    $retryAfter = $response->json()['retry_after'] ?? 1;
                    Log::warning('Discord rate limited', ['retry_after' => $retryAfter]);
                    sleep((int) ceil($retryAfter));

                    continue;
                }

                return $response;

            } catch (Exception $e) {
                if ($attempt === self::MAX_RETRIES) {
                    throw $e;
                }
                sleep($attempt); // Simple backoff
            }
        }

        throw new Exception('Max retries exceeded');
    }

    /**
     * Check if we're rate limited.
     */
    private function checkRateLimit(): void
    {
        // Check circuit breaker
        if ($this->isCircuitBreakerOpen()) {
            throw new Exception('Circuit breaker is open');
        }

        if (Cache::get(self::RATE_LIMIT_KEY)) {
            throw new Exception('Rate limited');
        }
    }

    /**
     * Handle rate limit headers.
     */
    private function handleRateLimit(Response $response): void
    {
        $headers = $response->headers();

        // Update rate limit stats
        $this->updateRateLimitStats($headers, $response);

        if (isset($headers['X-RateLimit-Remaining'][0]) && $headers['X-RateLimit-Remaining'][0] === '0') {
            $resetAfter = (int) ($headers['X-RateLimit-Reset-After'][0] ?? 1);
            Cache::put(self::RATE_LIMIT_KEY, true, $resetAfter);
        }
    }

    /**
     * Update rate limit statistics.
     */
    private function updateRateLimitStats(array $headers, Response $response): void
    {
        $stats = Cache::get(self::RATE_LIMIT_STATS_KEY, [
            'requests' => 0,
            'errors' => 0,
            'rate_limited' => 0,
            'last_reset' => now()->timestamp,
        ]);

        $stats['requests']++;

        if ($response->failed()) {
            $stats['errors']++;
        }

        if ($response->status() === 429) {
            $stats['rate_limited']++;
            $this->incrementCircuitBreaker();
        }

        // Extract rate limit headers
        $stats['limit'] = isset($headers['X-RateLimit-Limit'][0])
            ? (int) $headers['X-RateLimit-Limit'][0]
            : null;
        $stats['remaining'] = isset($headers['X-RateLimit-Remaining'][0])
            ? (int) $headers['X-RateLimit-Remaining'][0]
            : null;
        $stats['reset'] = isset($headers['X-RateLimit-Reset'][0])
            ? (int) $headers['X-RateLimit-Reset'][0]
            : null;
        $stats['reset_after'] = isset($headers['X-RateLimit-Reset-After'][0])
            ? (float) $headers['X-RateLimit-Reset-After'][0]
            : null;
        $stats['global'] = isset($headers['X-RateLimit-Global'][0])
            ? $headers['X-RateLimit-Global'][0] === 'true'
            : false;

        Cache::put(self::RATE_LIMIT_STATS_KEY, $stats, 3600); // Cache for 1 hour
    }

    /**
     * Check if circuit breaker is open.
     */
    private function isCircuitBreakerOpen(): bool
    {
        return self::getCircuitBreakerState() === 'open';
    }

    /**
     * Increment circuit breaker failure count.
     */
    private function incrementCircuitBreaker(): void
    {
        $breaker = Cache::get(self::CIRCUIT_BREAKER_KEY, [
            'failures' => 0,
            'last_failure' => null,
            'state' => 'closed',
        ]);

        $breaker['failures']++;
        $breaker['last_failure'] = now();

        if ($breaker['failures'] >= self::CIRCUIT_BREAKER_THRESHOLD) {
            $breaker['state'] = 'open';
        }

        Cache::put(self::CIRCUIT_BREAKER_KEY, $breaker, 3600);
    }
}
