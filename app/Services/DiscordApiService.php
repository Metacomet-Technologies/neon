<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Discord\Discord;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * Comprehensive Discord API service with rate limiting and token management.
 */
final class DiscordApiService
{
    private const DISCORD_API_BASE = 'https://discord.com/api/v10';
    private const GLOBAL_RATE_LIMIT_KEY = 'discord_global_rate_limit';
    private const INVALID_REQUESTS_KEY = 'discord_invalid_requests';
    private const CIRCUIT_BREAKER_KEY = 'discord_circuit_breaker';
    private const CLOUDFLARE_BAN_THRESHOLD = 10000;
    
    // Circuit breaker states
    private const CIRCUIT_CLOSED = 'closed';
    private const CIRCUIT_OPEN = 'open';
    private const CIRCUIT_HALF_OPEN = 'half_open';
    
    // Retry configuration
    private const MAX_RETRIES = 3;
    private const BASE_BACKOFF_MS = 1000;
    private const MAX_BACKOFF_MS = 32000;

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.discord.rest_api_url', self::DISCORD_API_BASE);
    }

    /**
     * Make a rate-limited request to Discord API with bot token.
     */
    public function request(string $method, string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->makeRequest($method, $endpoint, $data, $headers, 'bot');
    }

    /**
     * Make a rate-limited request to Discord API with user token.
     */
    public function requestAsUser(string $method, string $endpoint, string $userToken, array $data = [], array $headers = []): Response
    {
        return $this->makeRequest($method, $endpoint, $data, $headers, 'user', $userToken);
    }

    /**
     * GET request with bot token.
     */
    public function get(string $endpoint, array $query = [], array $headers = []): Response
    {
        return $this->request('GET', $endpoint, ['query' => $query], $headers);
    }

    /**
     * GET request with user token.
     */
    public function getAsUser(string $endpoint, string $userToken, array $query = [], array $headers = []): Response
    {
        return $this->requestAsUser('GET', $endpoint, $userToken, ['query' => $query], $headers);
    }

    /**
     * POST request with bot token.
     */
    public function post(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('POST', $endpoint, ['json' => $data], $headers);
    }

    /**
     * POST request with user token.
     */
    public function postAsUser(string $endpoint, string $userToken, array $data = [], array $headers = []): Response
    {
        return $this->requestAsUser('POST', $endpoint, $userToken, ['json' => $data], $headers);
    }

    /**
     * PUT request with bot token.
     */
    public function put(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('PUT', $endpoint, ['json' => $data], $headers);
    }

    /**
     * PUT request with user token.
     */
    public function putAsUser(string $endpoint, string $userToken, array $data = [], array $headers = []): Response
    {
        return $this->requestAsUser('PUT', $endpoint, $userToken, ['json' => $data], $headers);
    }

    /**
     * PATCH request with bot token.
     */
    public function patch(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('PATCH', $endpoint, ['json' => $data], $headers);
    }

    /**
     * PATCH request with user token.
     */
    public function patchAsUser(string $endpoint, string $userToken, array $data = [], array $headers = []): Response
    {
        return $this->requestAsUser('PATCH', $endpoint, $userToken, ['json' => $data], $headers);
    }

    /**
     * DELETE request with bot token.
     */
    public function delete(string $endpoint, array $headers = []): Response
    {
        return $this->request('DELETE', $endpoint, [], $headers);
    }

    /**
     * DELETE request with user token.
     */
    public function deleteAsUser(string $endpoint, string $userToken, array $headers = []): Response
    {
        return $this->requestAsUser('DELETE', $endpoint, $userToken, [], $headers);
    }

    /**
     * Get guild roles.
     */
    public function getGuildRoles(string $guildId): Collection
    {
        try {
            $response = $this->get("/guilds/{$guildId}/roles");
            
            if ($response->failed()) {
                Log::error("Failed to fetch roles for guild {$guildId}");
                throw new Exception('Failed to retrieve roles from the server.', 500);
            }

            return collect($response->json());
        } catch (Exception $e) {
            Log::error("Failed to fetch roles for guild {$guildId}", ['error' => $e->getMessage()]);
            throw new Exception('Failed to retrieve roles from the server.', 500);
        }
    }

    /**
     * Find role by name (case insensitive).
     */
    public function findRoleByName(string $guildId, string $roleName): ?array
    {
        $roles = $this->getGuildRoles($guildId);
        return $roles->first(fn ($role) => strcasecmp($role['name'], $roleName) === 0);
    }

    /**
     * Get guild member information.
     */
    public function getGuildMember(string $guildId, string $userId): array
    {
        try {
            $response = $this->get("/guilds/{$guildId}/members/{$userId}");

            if ($response->failed()) {
                throw new Exception('Failed to retrieve member information.', 404);
            }

            return $response->json();
        } catch (Exception) {
            throw new Exception('Failed to retrieve member information.', 404);
        }
    }

    /**
     * Get user's highest role position.
     */
    public function getUserHighestRolePosition(string $guildId, string $userId): int
    {
        try {
            $member = $this->getGuildMember($guildId, $userId);
            $roles = $member['roles'] ?? [];

            if (empty($roles)) {
                return 0;
            }

            $allRoles = $this->getGuildRoles($guildId);
            $userRoles = $allRoles->whereIn('id', $roles);

            return $userRoles->max('position') ?? 0;
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Assign role to user.
     */
    public function assignRole(string $guildId, string $userId, string $roleId): bool
    {
        $response = $this->put("/guilds/{$guildId}/members/{$userId}/roles/{$roleId}");
        return $response->successful();
    }

    /**
     * Remove role from user.
     */
    public function removeRole(string $guildId, string $userId, string $roleId): bool
    {
        $response = $this->delete("/guilds/{$guildId}/members/{$userId}/roles/{$roleId}");
        return $response->successful();
    }

    /**
     * Ban user from guild.
     */
    public function banUser(string $guildId, string $userId, int $deleteMessageDays = 7): bool
    {
        $response = $this->put("/guilds/{$guildId}/bans/{$userId}", [
            'delete_message_days' => $deleteMessageDays,
        ]);
        return $response->successful();
    }

    /**
     * Unban user from guild.
     */
    public function unbanUser(string $guildId, string $userId): bool
    {
        $response = $this->delete("/guilds/{$guildId}/bans/{$userId}");
        return $response->successful();
    }

    /**
     * Kick user from guild.
     */
    public function kickUser(string $guildId, string $userId): bool
    {
        $response = $this->delete("/guilds/{$guildId}/members/{$userId}");
        return $response->successful();
    }

    /**
     * Update channel information.
     */
    public function updateChannel(string $channelId, array $data): bool
    {
        $response = $this->patch("/channels/{$channelId}", $data);
        return $response->successful();
    }

    /**
     * Create new role.
     */
    public function createRole(string $guildId, array $roleData): ?array
    {
        $response = $this->post("/guilds/{$guildId}/roles", $roleData);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $guildId, string $roleId): bool
    {
        $response = $this->delete("/guilds/{$guildId}/roles/{$roleId}");
        return $response->successful();
    }

    /**
     * Create new channel.
     */
    public function createChannel(string $guildId, array $channelData): ?array
    {
        $response = $this->post("/guilds/{$guildId}/channels", $channelData);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Delete channel.
     */
    public function deleteChannel(string $channelId): bool
    {
        $response = $this->delete("/channels/{$channelId}");
        return $response->successful();
    }

    /**
     * Get everyone role for guild.
     */
    public function getEveryoneRole(string $guildId): ?array
    {
        $roles = $this->getGuildRoles($guildId);
        return $roles->first(fn ($role) => $role['name'] === '@everyone');
    }

    /**
     * Get current rate limit statistics for monitoring.
     *
     * @return array<string, mixed>
     */
    public function getRateLimitStats(): array
    {
        $globalRequestCount = Cache::get(self::GLOBAL_RATE_LIMIT_KEY . '_count', 0);
        $globalLimit = Cache::get(self::GLOBAL_RATE_LIMIT_KEY . '_limit', 50); // Fallback to common limit
        $invalidRequests = Cache::get(self::INVALID_REQUESTS_KEY . '_count', 0);
        $circuitState = Cache::get(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_CLOSED);
        
        return [
            'global_requests_current' => $globalRequestCount,
            'global_limit' => $globalLimit,
            'global_usage_percentage' => $globalLimit > 0 ? ($globalRequestCount / $globalLimit) * 100 : 0,
            'invalid_requests_count' => $invalidRequests,
            'invalid_requests_limit' => self::CLOUDFLARE_BAN_THRESHOLD,
            'invalid_requests_percentage' => ($invalidRequests / self::CLOUDFLARE_BAN_THRESHOLD) * 100,
            'circuit_breaker_state' => $circuitState,
            'approaching_global_limit' => $globalLimit > 0 && $globalRequestCount > ($globalLimit * 0.8),
            'approaching_invalid_limit' => $invalidRequests > (self::CLOUDFLARE_BAN_THRESHOLD * 0.8),
        ];
    }

    /**
     * Reset rate limit counters (for testing purposes).
     */
    public function resetRateLimits(): void
    {
        Cache::forget(self::GLOBAL_RATE_LIMIT_KEY . '_count');
        Cache::forget(self::GLOBAL_RATE_LIMIT_KEY . '_limit');
        Cache::forget(self::INVALID_REQUESTS_KEY . '_count');
        Cache::forget(self::CIRCUIT_BREAKER_KEY . '_state');
        Cache::forget(self::CIRCUIT_BREAKER_KEY . '_failures');
        Cache::forget(self::CIRCUIT_BREAKER_KEY . '_last_failure');
        
        // Clear all route-specific rate limits
        $routes = Cache::get('discord_route_keys', []);
        foreach ($routes as $routeKey) {
            Cache::forget("discord_route_limit_{$routeKey}");
            Cache::forget("discord_route_remaining_{$routeKey}");
            Cache::forget("discord_route_reset_{$routeKey}");
        }
    }

    /**
     * Core request method with rate limiting.
     */
    private function makeRequest(string $method, string $endpoint, array $data, array $headers, string $tokenType, ?string $userToken = null): Response
    {
        $this->checkCircuitBreaker();
        
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $routeKey = $this->getRouteKey($method, $endpoint);
        
        $attempt = 0;
        while ($attempt < self::MAX_RETRIES) {
            try {
                $this->checkRouteRateLimit($routeKey);
                
                $response = $this->makeHttpRequest($method, $url, $data, $headers, $tokenType, $userToken);
                
                $this->handleRateLimitHeaders($response, $routeKey);
                $this->updateGlobalRequestCount();
                
                if ($response->successful()) {
                    $this->recordSuccessfulRequest();
                    return $response;
                }
                
                if ($response->status() === 429) {
                    $this->handleRateLimit($response, $routeKey);
                    $attempt++;
                    continue;
                }
                
                if ($response->clientError()) {
                    $this->recordInvalidRequest();
                }
                
                if ($response->serverError()) {
                    $this->recordServerError();
                    $this->waitWithExponentialBackoff($attempt);
                    $attempt++;
                    continue;
                }
                
                return $response;
                
            } catch (Exception $e) {
                $this->recordRequestError($e);
                
                if ($attempt === self::MAX_RETRIES - 1) {
                    throw $e;
                }
                
                $this->waitWithExponentialBackoff($attempt);
                $attempt++;
            }
        }
        
        throw new Exception('Maximum retry attempts exceeded');
    }

    /**
     * Make the actual HTTP request.
     */
    private function makeHttpRequest(string $method, string $url, array $data, array $headers, string $tokenType, ?string $userToken): Response
    {
        $client = Http::timeout(30)->withHeaders($headers);
        
        if ($tokenType === 'bot') {
            $client = $client->withToken(config('discord.token'), 'Bot');
        } elseif ($tokenType === 'user' && $userToken) {
            $client = $client->withToken($userToken);
        } else {
            throw new Exception('Invalid token type or missing user token');
        }
        
        return match (strtoupper($method)) {
            'GET' => $client->get($url, $data['query'] ?? []),
            'POST' => $client->post($url, $data['json'] ?? []),
            'PUT' => $client->put($url, $data['json'] ?? []),
            'PATCH' => $client->patch($url, $data['json'] ?? []),
            'DELETE' => $client->delete($url),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Generate a route key for rate limiting.
     */
    private function getRouteKey(string $method, string $endpoint): string
    {
        // Remove major parameters for route grouping
        $route = preg_replace('/\/\d+/', '/{id}', $endpoint);
        $route = preg_replace('/\/[a-f0-9]{16,}/', '/{snowflake}', $route);
        
        return strtoupper($method) . ':' . $route;
    }

    /**
     * Check circuit breaker state.
     */
    private function checkCircuitBreaker(): void
    {
        $state = Cache::get(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_CLOSED);
        
        if ($state === self::CIRCUIT_OPEN) {
            $lastFailure = Cache::get(self::CIRCUIT_BREAKER_KEY . '_last_failure', 0);
            $cooldownPeriod = 60; // 1 minute
            
            if (time() - $lastFailure > $cooldownPeriod) {
                Cache::put(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_HALF_OPEN, 300);
            } else {
                throw new Exception('Circuit breaker is open - Discord API temporarily unavailable');
            }
        }
    }

    /**
     * Check route-specific rate limit.
     */
    private function checkRouteRateLimit(string $routeKey): void
    {
        $remaining = Cache::get("discord_route_remaining_{$routeKey}");
        $resetTime = Cache::get("discord_route_reset_{$routeKey}");
        
        if ($remaining !== null && $remaining <= 0 && $resetTime && time() < $resetTime) {
            $waitTime = $resetTime - time();
            throw new Exception("Route rate limit exceeded for {$routeKey} - wait {$waitTime} seconds");
        }
    }

    /**
     * Handle rate limit headers from Discord response.
     */
    private function handleRateLimitHeaders(Response $response, string $routeKey): void
    {
        $headers = $response->headers();
        
        // Global rate limit headers
        if (isset($headers['X-RateLimit-Global'][0]) && $headers['X-RateLimit-Global'][0] === 'true') {
            $retryAfter = (int) ($headers['Retry-After'][0] ?? 1);
            Cache::put(self::GLOBAL_RATE_LIMIT_KEY . '_blocked', true, $retryAfter);
        }
        
        // Extract global limit from headers when available
        if (isset($headers['X-RateLimit-Global-Limit'][0])) {
            $globalLimit = (int) $headers['X-RateLimit-Global-Limit'][0];
            Cache::put(self::GLOBAL_RATE_LIMIT_KEY . '_limit', $globalLimit, 3600);
        }
        
        // Route-specific rate limit headers
        if (isset($headers['X-RateLimit-Limit'][0])) {
            $limit = (int) $headers['X-RateLimit-Limit'][0];
            $remaining = (int) ($headers['X-RateLimit-Remaining'][0] ?? 0);
            $resetAfter = (float) ($headers['X-RateLimit-Reset-After'][0] ?? 1);
            
            Cache::put("discord_route_limit_{$routeKey}", $limit, 300);
            Cache::put("discord_route_remaining_{$routeKey}", $remaining, $resetAfter);
            Cache::put("discord_route_reset_{$routeKey}", time() + (int) $resetAfter, $resetAfter);
            
            // Store route key for cleanup
            $routes = Cache::get('discord_route_keys', []);
            if (!in_array($routeKey, $routes)) {
                $routes[] = $routeKey;
                Cache::put('discord_route_keys', $routes, 3600);
            }
        }
    }

    /**
     * Handle 429 rate limit response.
     */
    private function handleRateLimit(Response $response, string $routeKey): void
    {
        try {
            $body = $response->json();
            $retryAfter = $body['retry_after'] ?? 1;
            $global = $body['global'] ?? false;
            
            if ($global) {
                Cache::put(self::GLOBAL_RATE_LIMIT_KEY . '_blocked', true, $retryAfter);
                Log::warning('Discord global rate limit hit', ['retry_after' => $retryAfter]);
            } else {
                Cache::put("discord_route_remaining_{$routeKey}", 0, $retryAfter);
                Cache::put("discord_route_reset_{$routeKey}", time() + (int) $retryAfter, $retryAfter);
                Log::warning('Discord route rate limit hit', ['route' => $routeKey, 'retry_after' => $retryAfter]);
            }
            
            // Wait for the retry_after period
            sleep((int) ceil($retryAfter));
            
        } catch (JsonException $e) {
            Log::error('Failed to parse rate limit response', ['error' => $e->getMessage()]);
            sleep(1); // Default 1 second wait
        }
    }

    /**
     * Update global request count.
     */
    private function updateGlobalRequestCount(): void
    {
        $key = self::GLOBAL_RATE_LIMIT_KEY . '_count';
        $ttl = 1; // 1 second window
        
        Cache::increment($key, 1);
        if (!Cache::has($key . '_ttl')) {
            Cache::put($key . '_ttl', true, $ttl);
            Cache::put($key, 0, $ttl);
        }
    }

    /**
     * Record an invalid request (4xx error).
     */
    private function recordInvalidRequest(): void
    {
        $key = self::INVALID_REQUESTS_KEY . '_count';
        $ttl = 600; // 10 minutes
        
        $count = Cache::increment($key, 1);
        if (!Cache::has($key . '_ttl')) {
            Cache::put($key . '_ttl', true, $ttl);
        }
        
        if ($count >= self::CLOUDFLARE_BAN_THRESHOLD) {
            Log::critical('Approaching Cloudflare ban threshold', ['invalid_requests' => $count]);
            Cache::put(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_OPEN, 3600);
        }
    }

    /**
     * Record a successful request.
     */
    private function recordSuccessfulRequest(): void
    {
        $state = Cache::get(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_CLOSED);
        
        if ($state === self::CIRCUIT_HALF_OPEN) {
            Cache::put(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_CLOSED, 300);
            Cache::forget(self::CIRCUIT_BREAKER_KEY . '_failures');
        }
    }

    /**
     * Record a server error (5xx).
     */
    private function recordServerError(): void
    {
        $failures = Cache::increment(self::CIRCUIT_BREAKER_KEY . '_failures', 1);
        Cache::put(self::CIRCUIT_BREAKER_KEY . '_last_failure', time(), 3600);
        
        if ($failures >= 5) {
            Cache::put(self::CIRCUIT_BREAKER_KEY . '_state', self::CIRCUIT_OPEN, 3600);
            Log::warning('Circuit breaker opened due to server errors', ['failures' => $failures]);
        }
    }

    /**
     * Record a request error (exception).
     */
    private function recordRequestError(Exception $e): void
    {
        Log::error('Discord API request error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        $this->recordServerError();
    }

    /**
     * Wait with exponential backoff.
     */
    private function waitWithExponentialBackoff(int $attempt): void
    {
        $backoff = min(self::BASE_BACKOFF_MS * (2 ** $attempt), self::MAX_BACKOFF_MS);
        $jitter = random_int(0, (int) ($backoff * 0.1)); // Add 10% jitter
        
        usleep(($backoff + $jitter) * 1000); // Convert to microseconds
    }

    /**
     * Perform batch operations with rate limiting.
     */
    public function batchOperation(array $items, callable $operation, int $batchSize = 5): array
    {
        $results = ['successful' => [], 'failed' => []];
        $chunks = array_chunk($items, $batchSize);

        foreach ($chunks as $batchIndex => $batch) {
            foreach ($batch as $item) {
                try {
                    $success = $operation($item);
                    if ($success) {
                        $results['successful'][] = $item;
                    } else {
                        $results['failed'][] = $item;
                    }
                } catch (Exception $e) {
                    $results['failed'][] = $item;
                    Log::error('Batch operation failed', ['item' => $item, 'error' => $e->getMessage()]);
                }
            }

            // Small delay between batches
            if ($batchIndex < count($chunks) - 1) {
                usleep(500000); // 0.5 seconds
            }
        }

        return $results;
    }


    /**
     * Refresh Discord OAuth token for a user.
     */
    public function refreshUserToken(User $user): ?string
    {
        Log::info('Refreshing Discord token for user', [
            'user_id' => $user->id,
        ]);

        $response = Http::post('https://discord.com/api/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.discord.client_id'),
            'client_secret' => config('services.discord.client_secret'),
            'refresh_token' => $user->refresh_token,
        ]);

        if ($response->failed()) {
            Log::error('Failed to refresh Discord token', [
                'user_id' => $user->id,
                'response' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();
        $user->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 0),
            'updated_at' => now(),
        ]);

        Log::info('Discord token refreshed successfully', [
            'user_id' => $user->id,
        ]);

        return $data['access_token'];
    }

    /**
     * Validate if a Discord channel name is valid.
     *
     * @return array{is_valid: bool, message: string}
     */
    public function validateChannelName(string $channelName): array
    {
        $maxLength = 100;
        $pattern = '/^[a-z0-9_-]+$/';

        if (strlen($channelName) > $maxLength) {
            return [
                'is_valid' => false,
                'message' => "The channel name must not exceed {$maxLength} characters.",
            ];
        }

        if (! preg_match($pattern, $channelName)) {
            return [
                'is_valid' => false,
                'message' => 'The channel name contains invalid characters. Only lowercase letters, numbers, hyphens, and underscores are allowed.',
            ];
        }

        return [
            'is_valid' => true,
            'message' => 'The channel name is valid.',
        ];
    }

    /**
     * Move user to voice channel.
     */
    public function moveUserToChannel(string $guildId, string $userId, string $channelId): bool
    {
        return $this->discord->guild($guildId)->moveMemberToChannel($userId, $channelId);
    }

    /**
     * Set guild AFK channel.
     */
    public function setGuildAfkChannel(string $guildId, string $channelId, int $timeout = 300): bool
    {
        return $this->discord->guild($guildId)->setAfkChannel($channelId, $timeout);
    }

    /**
     * Set guild boost progress bar.
     */
    public function setGuildBoostProgressBar(string $guildId, bool $enabled): bool
    {
        return $this->discord->guild($guildId)->setBoostProgressBar($enabled);
    }

    /**
     * Prune inactive members.
     */
    public function pruneInactiveMembers(string $guildId, int $days): array
    {
        return $this->discord->guild($guildId)->pruneMembers($days);
    }

    /**
     * Update channel permissions.
     */
    public function updateChannelPermissions(string $channelId, string $overwriteId, array $permissions): bool
    {
        return $this->discord->channel($channelId)->editPermissions($overwriteId, $permissions, 0);
    }

    /**
     * Send notification (alias for sending message).
     */
    public function sendNotification(string $channelId, array $data): bool
    {
        try {
            $this->discord->channel($channelId)->send($data['message'] ?? $data);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get guild channels.
     */
    public function getGuildChannels(string $guildId): Collection
    {
        return $this->discord->guild($guildId)->channels()->get();
    }
}
