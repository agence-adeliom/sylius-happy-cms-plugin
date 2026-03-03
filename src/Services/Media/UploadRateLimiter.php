<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Media;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Rate limiter service for upload requests
 *
 * Prevents abuse by limiting the number of upload requests per time period.
 * Can temporarily ban users/IPs after exceeding limits.
 */
class UploadRateLimiter
{
    private bool $enabled;

    private int $maxRequests;

    private int $period;

    private int $banDuration;

    private CacheItemPoolInterface $cache;

    private LoggerInterface $logger;

    public function __construct(
        bool $enabled,
        int $maxRequests,
        int $period,
        int $banDuration,
        CacheItemPoolInterface $cache,
        ?LoggerInterface $logger = null,
    ) {
        $this->enabled = $enabled;
        $this->maxRequests = $maxRequests;
        $this->period = $period;
        $this->banDuration = $banDuration;
        $this->cache = $cache;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Check if the identifier is allowed to make a request
     *
     * @param string $identifier Unique identifier (IP, user ID, session ID)
     *
     * @return bool True if allowed, false if rate limit exceeded
     */
    public function checkLimit(string $identifier): bool
    {
        // If rate limiting is disabled, always allow
        if (!$this->enabled) {
            return true;
        }

        // Check if identifier is banned
        if ($this->isBanned($identifier)) {
            $this->logger->warning('Upload request blocked: identifier is banned', [
                'identifier' => $identifier,
                'ban_expires' => $this->getBanExpiration($identifier),
            ]);

            return false;
        }

        // Get current request count
        $count = $this->getRequestCount($identifier);

        // Check if limit exceeded
        if ($count >= $this->maxRequests) {
            $this->logger->warning('Upload rate limit exceeded', [
                'identifier' => $identifier,
                'count' => $count,
                'limit' => $this->maxRequests,
                'period' => $this->period,
            ]);

            // Apply ban if configured
            if ($this->banDuration > 0) {
                $this->ban($identifier);
            }

            return false;
        }

        // Increment request count
        $this->incrementRequestCount($identifier);

        return true;
    }

    /**
     * Check if identifier is currently banned
     */
    public function isBanned(string $identifier): bool
    {
        $key = $this->getBanCacheKey($identifier);
        $item = $this->cache->getItem($key);

        return $item->isHit();
    }

    /**
     * Get ban expiration timestamp
     *
     * @return int|null Timestamp when ban expires, null if not banned
     */
    public function getBanExpiration(string $identifier): ?int
    {
        $key = $this->getBanCacheKey($identifier);
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return null;
        }

        /** @var string|int $value */
        $value = $item->get();

        return is_string($value) ? (int) $value : $value;
    }

    /**
     * Get remaining ban time in seconds
     */
    public function getRemainingBanTime(string $identifier): int
    {
        $expiration = $this->getBanExpiration($identifier);

        if ($expiration === null) {
            return 0;
        }

        $remaining = $expiration - time();

        return max(0, $remaining);
    }

    /**
     * Ban an identifier
     */
    private function ban(string $identifier): void
    {
        $key = $this->getBanCacheKey($identifier);
        $item = $this->cache->getItem($key);

        $expiresAt = time() + $this->banDuration;
        $item->set($expiresAt);
        $item->expiresAfter($this->banDuration);

        $this->cache->save($item);

        $this->logger->info('Identifier banned for excessive upload requests', [
            'identifier' => $identifier,
            'duration' => $this->banDuration,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
        ]);
    }

    /**
     * Manually unban an identifier
     */
    public function unban(string $identifier): void
    {
        $key = $this->getBanCacheKey($identifier);
        $this->cache->deleteItem($key);

        $this->logger->info('Identifier unbanned', [
            'identifier' => $identifier,
        ]);
    }

    /**
     * Get current request count for identifier
     */
    private function getRequestCount(string $identifier): int
    {
        $key = $this->getCountCacheKey($identifier);
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            return 0;
        }

        /** @var string|int $value */
        $value = $item->get();

        return is_string($value) ? (int) $value : $value;
    }

    /**
     * Increment request count
     */
    private function incrementRequestCount(string $identifier): void
    {
        $key = $this->getCountCacheKey($identifier);
        $item = $this->cache->getItem($key);

        /** @var string|int $value */
        $value = $item->get();

        $count = $item->isHit() ? is_string($value) ? (int) $value : $value : 0;
        ++$count;

        $item->set($count);
        $item->expiresAfter($this->period);

        $this->cache->save($item);
    }

    /**
     * Reset request count for identifier
     */
    public function reset(string $identifier): void
    {
        $key = $this->getCountCacheKey($identifier);
        $this->cache->deleteItem($key);
    }

    /**
     * Get remaining requests before hitting limit
     */
    public function getRemainingRequests(string $identifier): int
    {
        if (!$this->enabled) {
            return \PHP_INT_MAX;
        }

        if ($this->isBanned($identifier)) {
            return 0;
        }

        $count = $this->getRequestCount($identifier);

        return max(0, $this->maxRequests - $count);
    }

    /**
     * Get rate limit info for identifier
     *
     * @return array{
     *     enabled: bool,
     *     is_banned: bool,
     *     remaining_requests: int,
     *     max_requests: int,
     *     period: int,
     *     ban_expires_in: int,
     * }
     */
    public function getInfo(string $identifier): array
    {
        return [
            'enabled' => $this->enabled,
            'is_banned' => $this->isBanned($identifier),
            'remaining_requests' => $this->getRemainingRequests($identifier),
            'max_requests' => $this->maxRequests,
            'period' => $this->period,
            'ban_expires_in' => $this->getRemainingBanTime($identifier),
        ];
    }

    /**
     * Generate cache key for ban status
     */
    private function getBanCacheKey(string $identifier): string
    {
        return 'happy_cms_upload_ban_' . md5($identifier);
    }

    /**
     * Generate cache key for request count
     */
    private function getCountCacheKey(string $identifier): string
    {
        return 'happy_cms_upload_count_' . md5($identifier);
    }

    /**
     * Check if rate limiting is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get max requests limit
     */
    public function getMaxRequests(): int
    {
        return $this->maxRequests;
    }

    /**
     * Get period in seconds
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * Get ban duration in seconds
     */
    public function getBanDuration(): int
    {
        return $this->banDuration;
    }
}
