<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Services;

use App\Modules\Core\AI\DTO\Message;
use DateTimeImmutable;

class MessageManager
{
    public function __construct(
        private readonly SessionManager $sessionManager,
    ) {}

    /**
     * Append a message to a session transcript.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @param  string  $sessionId  Session UUID
     * @param  Message  $message  Message to append
     */
    public function append(int $employeeId, string $sessionId, Message $message): void
    {
        $path = $this->sessionManager->transcriptPath($employeeId, $sessionId);

        file_put_contents(
            $path,
            $message->toJsonLine()."\n",
            FILE_APPEND | LOCK_EX,
        );

        $this->sessionManager->touch($employeeId, $sessionId);
    }

    /**
     * Append a user message to a session transcript.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @param  string  $sessionId  Session UUID
     * @param  string  $content  Message content
     */
    public function appendUserMessage(int $employeeId, string $sessionId, string $content): Message
    {
        $message = new Message(
            role: 'user',
            content: $content,
            timestamp: new DateTimeImmutable,
        );

        $this->append($employeeId, $sessionId, $message);

        return $message;
    }

    /**
     * Append an assistant message to a session transcript.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @param  string  $sessionId  Session UUID
     * @param  string  $content  Message content
     * @param  string|null  $runId  Runtime run ID
     * @param  array<string, mixed>  $meta  Runtime metadata (model, latency, tokens)
     */
    public function appendAssistantMessage(
        int $employeeId,
        string $sessionId,
        string $content,
        ?string $runId = null,
        array $meta = [],
    ): Message {
        $message = new Message(
            role: 'assistant',
            content: $content,
            timestamp: new DateTimeImmutable,
            runId: $runId,
            meta: $meta,
        );

        $this->append($employeeId, $sessionId, $message);

        return $message;
    }

    /**
     * Read all messages from a session transcript in order.
     *
     * @param  int  $employeeId  Digital Worker employee ID
     * @param  string  $sessionId  Session UUID
     * @return list<Message>
     */
    public function read(int $employeeId, string $sessionId): array
    {
        $path = $this->sessionManager->transcriptPath($employeeId, $sessionId);

        if (! file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $messages = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);

            if ($data !== null) {
                $messages[] = Message::fromJsonLine($data);
            }
        }

        return $messages;
    }
}
