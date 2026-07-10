<?php

declare(strict_types=1);

namespace BeGenius\Ussd\Responses;

use Illuminate\Http\Response;

/**
 * UssdResponse
 *
 * Represents a USSD response sent back to the telecom gateway.
 *
 * In the USSD protocol, responses have two types:
 *
 * - CON (Continue): The session remains open. The user sees the message
 *   and can reply. The gateway expects more input.
 *   Example: "CON Welcome\n1. Balance\n2. Transfer"
 *
 * - END (End): The session is terminated. The user sees the message
 *   and the USSD session ends.
 *   Example: "END Thank you for using our service."
 *
 * The gateway reads the first three characters of the response to
 * determine the type. Invalid prefixes may cause gateway errors.
 *
 * This class ensures that responses are always correctly formatted.
 */
class UssdResponse
{
    private const TYPE_CONTINUE = 'CON';
    private const TYPE_END      = 'END';

    /**
     * @param string $type    CON or END
     * @param string $message The message body to display to the user
     * @param array  $extra   Optional extra data (for logging/debugging)
     */
    private function __construct(
        private readonly string $type,
        private readonly string $message,
        private readonly array $extra = [],
    ) {}

    /**
     * Create a CON (continue) response.
     *
     * The session stays open and the user will be prompted for input.
     */
    public static function continue(string $message, array $extra = []): self
    {
        return new self(self::TYPE_CONTINUE, $message, $extra);
    }

    /**
     * Create an END (end) response.
     *
     * The session is terminated. The user sees this message and the
     * USSD dialog closes.
     */
    public static function end(string $message, array $extra = []): self
    {
        return new self(self::TYPE_END, $message, $extra);
    }

    /**
     * Convert this USSD response into a Laravel HTTP response.
     *
     * The format is:
     *   CON <message>\n
     * or
     *   END <message>\n
     *
     * Most gateways expect the response as plain text with
     * Content-Type: text/plain.
     */
    public function toHttpResponse(): Response
    {
        $body = $this->type.' '.$this->message."\n";

        return new Response($body, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Get the raw response string (e.g., "CON Welcome\n").
     */
    public function toString(): string
    {
        return $this->type.' '.$this->message."\n";
    }

    public function type(): string
    {
        return $this->type;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function isContinue(): bool
    {
        return $this->type === self::TYPE_CONTINUE;
    }

    public function isEnd(): bool
    {
        return $this->type === self::TYPE_END;
    }
}
