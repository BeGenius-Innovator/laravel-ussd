<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Core\UssdSession;
use BeGenius\Ussd\Drivers\Session\RedisSessionDriver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * RedisSessionDriverTest
 *
 * Tests the Redis session driver with a mocked Redis facade.
 */
class RedisSessionDriverTest extends TestCase
{
    private RedisSessionDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        Redis::shouldReceive('connection')
            ->with('default')
            ->andReturnSelf()
            ->byDefault();
    }

    /** @test */
    public function it_stores_and_retrieves_a_session()
    {
        $key = 'ussd:session:redis_test_1';

        Redis::shouldReceive('connection')->with('default')->andReturnSelf();
        Redis::shouldReceive('setex')
            ->once()
            ->with($key, \Mockery::type('int'), \Mockery::type('string'));
        Redis::shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturn(json_encode([
                'session_id'    => 'redis_test_1',
                'phone_number'  => '22670000000',
                'network'       => 'ORANGE',
                'current_state' => 'main_menu',
                'data'          => ['amount' => '5000'],
                'created_at'    => '2025-01-01T00:00:00+00:00',
                'updated_at'    => '2025-01-01T00:00:00+00:00',
            ]));

        $driver = new RedisSessionDriver(ttlMinutes: 2);

        $session = new UssdSession(
            sessionId:    'redis_test_1',
            phoneNumber:  '22670000000',
            network:      'ORANGE',
            currentState: 'main_menu',
            data:         ['amount' => '5000'],
            createdAt:    Carbon::parse('2025-01-01'),
            updatedAt:    Carbon::parse('2025-01-01'),
        );

        $driver->save($session);

        $found = $driver->find('redis_test_1');

        $this->assertNotNull($found);
        $this->assertEquals('redis_test_1', $found->sessionId());
        $this->assertEquals('22670000000', $found->phoneNumber());
        $this->assertEquals('ORANGE', $found->network());
        $this->assertEquals('main_menu', $found->currentState());
        $this->assertEquals('5000', $found->get('amount'));
    }

    /** @test */
    public function it_returns_null_for_nonexistent_session()
    {
        Redis::shouldReceive('connection')->with('default')->andReturnSelf();
        Redis::shouldReceive('get')
            ->once()
            ->with('ussd:session:nonexistent')
            ->andReturn(null);

        $driver = new RedisSessionDriver();

        $this->assertNull($driver->find('nonexistent'));
    }

    /** @test */
    public function it_deletes_a_session()
    {
        Redis::shouldReceive('connection')->with('default')->andReturnSelf();
        Redis::shouldReceive('del')
            ->once()
            ->with('ussd:session:delete_me');

        $driver = new RedisSessionDriver();
        $driver->delete('delete_me');

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function it_uses_custom_redis_connection()
    {
        Redis::shouldReceive('connection')
            ->once()
            ->with('cache')
            ->andReturnSelf();
        Redis::shouldReceive('get')
            ->once()
            ->with('ussd:session:custom')
            ->andReturn(null);

        $driver = new RedisSessionDriver(connection: 'cache');
        $driver->find('custom');

        $this->addToAssertionCount(1);
    }
}
