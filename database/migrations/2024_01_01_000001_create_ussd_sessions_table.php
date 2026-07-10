<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the table for storing USSD sessions.
     *
     * Each row represents a single USSD session, identified by
     * the gateway's sessionId. The table stores:
     *
     * - session_id    : Gateway session identifier (primary-like)
     * - phone_number  : Subscriber's MSISDN
     * - network       : Network operator code
     * - current_state : Current menu or flow step name
     * - data          : JSON payload with temporary user data
     * - created_at    : Session start timestamp
     * - updated_at    : Last activity timestamp
     */
    public function up(): void
    {
        Schema::create('ussd_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->unique();
            $table->string('phone_number', 30);
            $table->string('network', 50)->nullable();
            $table->string('current_state', 100)->default('');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('phone_number');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ussd_sessions');
    }
};
