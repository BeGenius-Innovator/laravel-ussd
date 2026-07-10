<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Drivers\Session;

use BeGenius\Ussd\Contracts\SessionDriver;
use BeGenius\Ussd\Core\UssdSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * DatabaseSessionDriver
 *
 * Stores USSD sessions in a database table.
 *
 * This is the recommended driver for production use. It provides
 * persistence across server restarts and can be used in
 * load-balanced environments.
 */
class DatabaseSessionDriver implements SessionDriver
{
    public function __construct(
        private readonly string $table = 'ussd_sessions',
    ) {}

    public function find(string $sessionId): ?UssdSession
    {
        $row = DB::table($this->table)->where('session_id', $sessionId)->first();

        if ($row === null) {
            return null;
        }

        return new UssdSession(
            sessionId:    $row->session_id,
            phoneNumber:  $row->phone_number,
            network:      $row->network,
            currentState: $row->current_state ?? '',
            data:         isset($row->data) ? json_decode($row->data, true) ?? [] : [],
            createdAt:    isset($row->created_at) ? Carbon::parse($row->created_at) : null,
            updatedAt:    isset($row->updated_at) ? Carbon::parse($row->updated_at) : null,
        );
    }

    public function save(UssdSession $session): void
    {
        $data = [
            'session_id'    => $session->sessionId(),
            'phone_number'  => $session->phoneNumber(),
            'network'       => $session->network(),
            'current_state' => $session->currentState(),
            'data'          => json_encode($session->allData()),
            'updated_at'    => Carbon::now(),
        ];

        $existing = DB::table($this->table)
            ->where('session_id', $session->sessionId())
            ->first();

        if ($existing) {
            DB::table($this->table)
                ->where('session_id', $session->sessionId())
                ->update($data);
        } else {
            $data['created_at'] = Carbon::now();
            DB::table($this->table)->insert($data);
        }
    }

    public function delete(string $sessionId): void
    {
        DB::table($this->table)->where('session_id', $sessionId)->delete();
    }

    public function purgeExpired(int $lifetimeMinutes): int
    {
        $cutoff = Carbon::now()->subMinutes($lifetimeMinutes);

        return DB::table($this->table)
            ->where('updated_at', '<', $cutoff)
            ->delete();
    }
}
