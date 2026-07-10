<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Tests;

use BeGenius\Ussd\Core\UssdSession;
use BeGenius\Ussd\Drivers\Session\ArraySessionDriver;
use BeGenius\Ussd\Services\SessionManager;

/**
 * SessionManagerTest
 *
 * Tests the session management system.
 *
 * These tests verify:
 * - Session creation (loadOrCreate returns a new session)
 * - Session loading (loadOrCreate returns existing session)
 * - Session data storage (set/get/has/forget)
 * - Session expiration detection
 * - Session destruction
 */
class SessionManagerTest extends TestCase
{
    private SessionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new SessionManager(new ArraySessionDriver(), 2);
    }

    /** @test */
    public function it_creates_a_new_session_when_none_exists(): void
    {
        $session = $this->manager->loadOrCreate('new_session', '22670000000', 'ORANGE');

        $this->assertInstanceOf(UssdSession::class, $session);
        $this->assertEquals('new_session', $session->sessionId());
        $this->assertEquals('22670000000', $session->phoneNumber());
        $this->assertEquals('ORANGE', $session->network());
        $this->assertTrue($session->currentState() === '');
    }

    /** @test */
    public function it_loads_existing_session(): void
    {
        $session1 = $this->manager->loadOrCreate('existing', '22670000000', 'ORANGE');
        $session1->set('name', 'John');

        $session2 = $this->manager->loadOrCreate('existing', '22670000000', 'ORANGE');

        $this->assertSame($session1->sessionId(), $session2->sessionId());
        $this->assertEquals('John', $session2->get('name'));
    }

    /** @test */
    public function it_stores_and_retrieves_session_data(): void
    {
        $session = $this->manager->loadOrCreate('data_test', '22670000000', 'ORANGE');

        $session->set('amount', '5000');
        $session->set('recipient', '22671234567');

        $this->assertEquals('5000', $session->get('amount'));
        $this->assertEquals('22671234567', $session->get('recipient'));
        $this->assertTrue($session->has('amount'));
        $this->assertFalse($session->has('nonexistent'));
        $this->assertNull($session->get('nonexistent'));
        $this->assertEquals('default', $session->get('nonexistent', 'default'));
    }

    /** @test */
    public function it_removes_session_data(): void
    {
        $session = $this->manager->loadOrCreate('forget_test', '22670000000', 'ORANGE');
        $session->set('key', 'value');
        $this->assertTrue($session->has('key'));

        $session->forget('key');
        $this->assertFalse($session->has('key'));
    }

    /** @test */
    public function it_detects_expired_sessions(): void
    {
        $session = new UssdSession(
            sessionId:   'expired',
            phoneNumber: '22670000000',
            network:     'ORANGE',
            currentState: '',
            data:        [],
            createdAt:   null,
            updatedAt:   null,
        );

        $this->assertFalse($session->isExpired(2));

        $session = new UssdSession(
            sessionId:   'old',
            phoneNumber: '22670000000',
            network:     'ORANGE',
            currentState: '',
            data:        [],
            createdAt:   null,
            updatedAt:   \Carbon\Carbon::now()->subMinutes(10),
        );

        $this->assertTrue($session->isExpired(2));
    }

    /** @test */
    public function it_destroys_a_session(): void
    {
        $session = $this->manager->loadOrCreate('destroy_me', '22670000000', 'ORANGE');
        $this->assertNotNull($session);

        $this->manager->destroy('destroy_me');

        // After destroy, loadOrCreate should return a new session
        $newSession = $this->manager->loadOrCreate('destroy_me', '22670000000', 'ORANGE');
        $this->assertNotSame($session, $newSession);
    }

    /** @test */
    public function it_tracks_current_state(): void
    {
        $session = $this->manager->loadOrCreate('state_test', '22670000000', 'ORANGE');

        $this->assertEquals('', $session->currentState());

        $session->setCurrentState('main_menu');
        $this->assertEquals('main_menu', $session->currentState());
    }
}
