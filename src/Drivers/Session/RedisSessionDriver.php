<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Session;

use BeGenius\Ussd\Contracts\SessionDriver;
use BeGenius\Ussd\Core\UssdSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * RedisSessionDriver
 *
 * Stores USSD sessions in Redis for high-throughput production
 * environments. Redis provides fast read/write and built-in TTL
 * expiration.
 *
 * Session data is stored as a JSON string under the key:
 *   ussd:session:{sessionId}
 *
 * The Redis TTL is set to the configured session lifetime
 * to automatically expire stale sessions.
 */
class RedisSessionDriver implements SessionDriver
{
    private const KEY_PREFIX = 'ussd:session:';

    public function __construct(
        private readonly string $connection = 'default',
        private readonly ?int $ttlMinutes = null,
    ) {}

    public function find(string $sessionId): ?UssdSession
    {
        $data = Redis::connection($this->connection)->get($this->key($sessionId));

        if ($data === null) {
            return null;
        }

        $row = json_decode($data, true);

        if ($row === null) {
            return null;
        }

        return new UssdSession(
            sessionId:    $row['session_id'] ?? $sessionId,
            phoneNumber:  $row['phone_number'] ?? '',
            network:      $row['network'] ?? '',
            currentState: $row['current_state'] ?? '',
            data:         $row['data'] ?? [],
            createdAt:    isset($row['created_at']) ? Carbon::parse($row['created_at']) : Carbon::now(),
            updatedAt:    isset($row['updated_at']) ? Carbon::parse($row['updated_at']) : Carbon::now(),
        );
    }

    public function save(UssdSession $session): void
    {
        $data = json_encode([
            'session_id'    => $session->sessionId(),
            'phone_number'  => $session->phoneNumber(),
            'network'       => $session->network(),
            'current_state' => $session->currentState(),
            'data'          => $session->allData(),
            'created_at'    => $session->createdAt()?->toIso8601String(),
            'updated_at'    => $session->updatedAt()?->toIso8601String(),
        ]);

        $ttl = $this->ttlMinutes !== null ? $this->ttlMinutes * 60 : null;

        $redis = Redis::connection($this->connection);

        if ($ttl !== null) {
            $redis->setex($this->key($session->sessionId()), $ttl, $data);
        } else {
            $redis->set($this->key($session->sessionId()), $data);
        }
    }

    public function delete(string $sessionId): void
    {
        Redis::connection($this->connection)->del($this->key($sessionId));
    }

    public function purgeExpired(int $lifetimeMinutes): int
    {
        // Redis handles TTL expiration automatically.
        // This method exists for interface compatibility.
        return 0;
    }

    private function key(string $sessionId): string
    {
        return self::KEY_PREFIX.$sessionId;
    }
}
